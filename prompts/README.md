# DevAgency Prompts

- [rules/](rules/): unverhandelbare Regelpakete; universelle Regeln gelten immer.
- [sessions/](sessions/): genau ein Prozessmodus pro Heartbeat.
- [agents/](agents/): Rollenverträge ohne Modellbindung.
- [tasks/](tasks/): wiederverwendbare Prüf- und Auditaufträge.

Ladereihenfolge: `AGENTS.md` -> Rolle -> Task -> relevante Context-Dateien -> Regelpakete -> Session. Projektkontext und genehmigter Task haben Vorrang vor generischen Beispielen, aber nicht vor Security-/Approval-Gates.

Neue oder umbenannte Dateien werden in diesem Index, `TEMPLATE.md` und betroffenen Querverweisen nachgezogen.

Rollenübergreifende ausführbare Verfahren liegen unter [`skills/`](../skills/README.md). Regeln definieren Policy; Skills orchestrieren ihre Anwendung.
