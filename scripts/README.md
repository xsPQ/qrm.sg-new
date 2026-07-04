# Automation Contract

In einem konkreten Projekt liegen hier stabile, dokumentierte Einstiegspunkte für Build, Test, Lint, Security-Scan, Release, Healthcheck und Cleanup. Das Template liefert absichtlich keine stackabhängigen Skripte.

| Zweck | Projektbefehl | Pflicht-Gate |
|---|---|---|
| Build | `[realer Befehl]` | Exitcode 0 |
| Tests | `[realer Befehl]` | relevante Tests grün |
| Lint/Format | `[realer Befehl]` | keine Fehler |
| Security/Dependencies | `[realer Befehl]` | keine unakzeptierten kritischen Findings |
| Release | `[realer Befehl]` | reproduzierbares Artefakt |
| Healthcheck | `[realer Befehl]` | erwarteter Zustand |
| Cleanup | `[realer Befehl]` | nur generierte lokale Artefakte entfernt |

Regeln:

- Befehle laufen vom Repository-Root und liefern aussagekräftige Exitcodes.
- Skripte enthalten keine Secrets, User-Pfade oder versteckte Produktionszugriffe.
- Destruktive und produktive Operationen benötigen explizite Flags und Approvals.
- Dokumentation und Skriptverhalten bleiben synchron.
- Lokale Ausgaben liegen in ignorierten Verzeichnissen.

Nächstes Gate: Ein Task wird nur `ready`, wenn seine Verifikationsbefehle existieren oder ein begründeter manueller Nachweis definiert ist.
