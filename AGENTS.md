# DevAgency Agent Instructions

Kurzer verpflichtender Einstieg; das vollständige Manifest steht in [TEMPLATE.md](TEMPLATE.md).

## Pflicht-Ladereihenfolge

1. `TEMPLATE.md` und `prompts/rules/universal-rules.md`.
2. Eigene Rolle unter `prompts/agents/` und zugewiesene Skills unter `skills/`.
3. Issue samt `workflow_state`, Elternziel, Blockern, Kommentardelta und Review-/Approval-Pfad.
4. Task, Scope, Out-of-Scope, Akzeptanz, Verifikation und Budgetklasse.
5. Über `docs/context/README.md` nur relevanten Kontext.
6. Passende Regelpakete; dann Arbeitsverzeichnis und Git-Zustand prüfen.

Vor jeder Mutation `task-preflight` anwenden. Weitere Skills nach Tasktyp und `skills/README.md` laden. Skills erweitern den Ablauf, nie die Rollenautorität.

## Pflichtbestätigung

```text
Loaded rules:
- one task only
- no scope expansion
- tests required
- no secrets
- no destructive git commands
- update docs if behaviour changes

Task: <ID und Titel>
Workflow state: <state>
Scope: <Dateien/Bereiche>
Out of scope: <Grenzen>
Verification: <Kommandos/Checks>
Budget: <Klasse, Tier, Obergrenze>
```

Fehlt ein Pflichtfeld oder ist der Task nicht atomar, wird nicht geraten. Zulässige Ergebnisse sind `needs_refinement`, `split_required`, `blocked_dependency`, `blocked_budget` oder `blocked_human`.

## Ausführung

- Genau einen Task; keine Nebenfeatures oder opportunistischen Refactorings.
- Dateien und vorhandene Tests vor Änderungen lesen.
- Checkout/Ownership respektieren; keine destruktiven Git-Kommandos.
- Fremde Änderungen bewahren.
- Verhalten testen; Bugfixes benötigen Regressionstests.
- Keine Secrets, Produktionsdaten, `.env` oder lokalen Artefakte committen.
- Doku bei Verhaltens-, Architektur-, Betriebs- oder Sicherheitsänderung aktualisieren.
- Abhängigkeiten, Premium-Ressourcen und Produktion benötigen ihre Gates.

## Abschlussformat

```text
Result: done | needs_refinement | split_required | blocked_* | *_failed
Changed: <Dateien/Artefakte>
Verification: <Befehl -> Ergebnis>
Acceptance: <Kriterium -> Nachweis>
Attempts: <Zähler>
Budget used: <Quelle/Klasse>
Risks: <Rest-Risiken oder none>
Next owner/state: <Rolle und workflow_state>
```

Vor Heartbeat-Ende müssen ein gültiger nativer Paperclip-Status und ein dauerhafter Nachweis vorliegen.
