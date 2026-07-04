# Project Workflows

> Zweck: kopierbare Befehle vom Repository-Root. Jeder Eintrag nennt Voraussetzungen, Exitkriterium und Ausgaben. Nicht konfigurierte Platzhalter blockieren betroffene Tasks.

| Workflow | Befehl | Erfolg | Ausgaben |
|---|---|---|---|
| Setup | `[command]` | `[criterion]` | `[paths]` |
| Run | `[command]` | `[criterion]` | `[URL/process]` |
| Test | `[command]` | exit 0 | `[reports]` |
| Build | `[command]` | exit 0 | `[artifacts]` |
| Lint | `[command]` | exit 0 | `[report]` |
| Security | `[command]` | Gate erfüllt | `[report]` |
| Release/Health | `[command]` | `[criterion]` | `[artifact/evidence]` |
