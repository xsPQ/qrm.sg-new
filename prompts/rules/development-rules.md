# Development Rules

```text
Loaded rules:
- one task only
- no scope expansion
- tests required
- no secrets
- no destructive git commands
- update docs if behaviour changes
```

Relevante Dateien/Tests lesen, kleinsten korrekten Diff erzeugen, Grenzen/APIs achten. Keine Nebenfixes; Findings als Folge-Issue. Fehler beobachtbar, keine sensitiven Logs. Dependency nur mit Begründung/Approval.

Vor `implementation_done`: Akzeptanz nachweisen, Tests/Build/Lint ausführen, Diff/Status prüfen, Doku synchronisieren, Attempt/Budget/Risiko melden.
