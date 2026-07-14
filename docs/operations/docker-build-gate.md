# Docker-Build-Quality-Gate (OPS-2 / DEV-640)

> Wiederkehrendes Quality Gate: Der qrm.sg-Container wird regelmäßig und
> reproduzierbar gebaut. Build-Fehler erzeugen sofort ein BUG-Issue, ein
> erfolgreicher Build wird per Smoke-Test bestätigt, das Ergebnis ist
> dokumentiert.

Dieses Dokument ist das verbindliche Runbook für den wiederkehrenden
Docker-Build als Quality Gate (Akzeptanzkriterien aus DEV-640).

## 1. Ziel und Akzeptanz

| Akzeptanzkriterium | Umsetzung |
|---|---|
| Build läuft regelmäßig | Paperclip-Routine mit `schedule`-Trigger (wöchentlich) + CI-`schedule`-Cron auf dem `docker-image`-Job |
| Build-Fehler erzeugen sofort ein BUG-Issue | `docker-build-gate.sh` emittiert bei Fehlschlag einen strukturierten BUG-Block; der Routinen-Run legt daraus ein BUG-Issue an |
| Nach erfolgreichem Build folgt Smoke-Test | Skript fährt den Compose-Stack hoch, prüft `/health` auf HTTP 200, räumt ab |
| Ergebnis wird dokumentiert | JSON- + Markdown-Report je Lauf unter `build-results/`; Ergebnis-Kommentar im Routinen-Run |

## 2. Bestandteile

- **`scripts/docker-build-gate.sh`** — Automatisierung: Build → ggf. BUG → Smoke → Report.
- **Paperclip-Routine** `Wiederkehrender Docker-Build (Quality Gate)` — treibt die
  Wiederholung; Run-Issues werden dem Release Orchestrator zugewiesen.
- **CI** (`.github/workflows/ci.yml`) — der `docker-image`-Job baut auf `push`/`pull_request`
  und zusätzlich per `schedule`-Cron sowie manuellem `workflow_dispatch`.

## 3. Kadenz

- **Routine:** wöchentlich (Montag 06:00 UTC). Nach Sprint-/Meilenstein-Abschluss
  kann der Release Orchestrator einen manuellen Run auslösen (`POST /api/routines/{id}/run`).
- **CI-Cron:** täglich (01:00 UTC) als zusätzliche Infra-Absicherung.
- Concurrency `coalesce_if_active`, Catch-Up `skip_missed` — keine Run-Flut bei Ausfällen.

## 4. Ablauf eines Laufs

```text
Routinen-Feuerung -> Run-Issue (Release Orchestrator)
  -> docker-build-gate.sh
       1. Runtime erkennen (podman | docker)
       2. Produkt-Repo bereitstellen (Clone oder --repo)
       3. Image bauen  (docker/app/Dockerfile, target=production)
            FEHLER  -> Report + BUG-Block -> BUG-Issue anlegen -> Run = blocked/failed
            ERFOLG   -> weiter
       4. Smoke-Test: compose up, /health HTTP 200 erwarten, teardown
            FEHLER  -> Report -> BUG-Issue -> Run = blocked/failed
            ERFOLG   -> Report (status=pass) -> Run = done
  -> Ergebnis als Kommentar + Report-Referenz dokumentieren
```

Exit-Codes des Skripts: `0` grün, `1` Build fehlgeschlagen, `2` Smoke fehlgeschlagen,
`3` Konfigurations-/Laufzeitfehler.

## 5. Aufruf

```bash
# Live (benötigt podman/docker + curl; clont das Produkt-Repo)
scripts/docker-build-gate.sh

# Gegen einen lokalen Checkout, Image pushen
scripts/docker-build-gate.sh --repo /path/to/qrm.sg --push

# Verifikation ohne Container-Runtime
scripts/docker-build-gate.sh --dry-run
scripts/docker-build-gate.sh --dry-run --simulate failure   # übt BUG-Pfad
```

Wichtige Umgebungsvariablen (alle optional):

| Variable | Default | Bedeutung |
|---|---|---|
| `PRODUCT_REPO_URL` | `https://github.com/xsPQ/qrm.sg-new.git` | Produkt-Repo |
| `DOCKERFILE` | `docker/app/Dockerfile` | wird auf `./Dockerfile` gefallbackt |
| `BUILD_TARGET` | `production` | Multi-Stage-Ziel; `none` deaktiviert |
| `IMAGE_NAME` | `qrm-sg` | lokaler Image-Name |
| `REGISTRY_IMAGE` | `ghcr.io/xspq/qrm-sg` | Push-Ziel mit `--push` |
| `HEALTH_URL` | `http://127.0.0.1:8080/health` | Smoke-Endpoint |
| `COMPOSE_FILE` | `docker-compose.yml` | Stack-Definition |
| `HEALTH_TIMEOUT` | `120` | Sekunden auf `/health` |
| `BUILD_RESULTS_DIR` | `build-results` | Report-Verzeichnis |
| `PUSH_IMAGE` | `0` | `1` -> Image pushen |

## 6. Ergebnisse & Reports

Je Lauf entstehen unter `build-results/`:

- `build-<TS>.json` — maschinenlesbar (status, image, commit, runtime, Zeiten, health).
- `build-<TS>.md` — menschenlesbarer Report.
- `build-<TS>.log` — vollständiges Build-/Smoke-Log.

`build-results/` ist generiert und darf nicht committet werden (in `.gitignore` aufnehmen).

## 7. BUG-Handling bei Build-Fehler

Tritt ein Build- oder Smoke-Fehler auf, emittiert das Skript einen `BUG-REPORT`-Block
(Titel, Repro-Befehl, Image, Log-Pfad). Der zuständige Agent legt daraus ein BUG-Issue
an (Label/Vorgänger: der fehlschlagende Routinen-Run), verlinkt den Report und blockiert
den Run bis zur Behebung. Ein Beispiel ist [DEV-638](/DEV/issues/DEV-638) (BUG-1:
Dockerfile reparieren), das aus einem real gescheiterten Build hervorging.

## 8. Voraussetzungen für einen grünen Lauf

- Container-Runtime (`podman` oder `docker`) + `curl` im ausführenden Workspace.
- Funktionierender Build: `docker/app/Dockerfile` mit `target=production` (siehe [DEV-638](/DEV/issues/DEV-638)).
- `docker-compose.yml` mit den 6 Services (nginx, app, worker, scheduler, postgres, redis)
  und einem `/health`-Endpoint.

### 8.1 Laufzeit-Dateisystem (DEV-858)

`docker/app/Dockerfile` normalisiert `storage/` und `bootstrap/cache` nur zur
**Build-Zeit** auf `www-data`. Zur **Laufzeit** kann ein Host-Bind-Mount auf
`storage/` (oder Dateien eines als root laufenden Workers/Schedulers) die
Ownership zurück auf root setzen, worauf `tempnam()`/die Blade-Kompilierung in
`storage/framework/views` scheitert und jede Blade-Seite (`/login`, `/register`,
`/admin`, `/up`) HTTP 500 liefert.

Die autoritative Selbstheilung liegt daher im Entrypoint
`docker/app/entrypoint.sh`, der bei jedem Container-Start `storage/` und
`bootstrap/cache` wieder `www-data` übereignet und Worker/Scheduler vor dem
`exec` auf `www-data` droppen (`php-fpm` bleibt root, um seinen Pool zu
verwalten). Regression-Test: `docker/app/tests/test_storage_permissions.sh`
(läuft als root im gebauten Image); portabler Smoke ohne Docker:
`docker/app/tests/smoke_entrypoint_standalone.sh`.

Die reproduciblen 6 Services stehen nun committed in `docker-compose.yml`
(nginx :8080 → app:9000, postgres, redis). Nach einem Rebuild des App-Images
ggf. `docker compose down -v` ausführen, damit das `webroot`-Volume neu vom
Image gesät wird.

Solange [DEV-638](/DEV/issues/DEV-638) (Dockerfile-Reparatur) offen ist, wird der
wiederkehrende Lauf erwartungsgemäß rot und erzeugt BUG-Issues — das ist das Gate am
Arbeiten, kein Defekt.

## 9. Einordnung

Dieses Gate ergänzt die bestehenden Regeln in
[docker-rules.md](docker-rules.md), [release-rules.md](release-rules.md) und
[testing-rules.md](testing-rules.md) um den wiederkehrenden Betriebs-Check.
Produktions-Freigaben und Push in die Registry bleiben separat an Approvals gebunden.
