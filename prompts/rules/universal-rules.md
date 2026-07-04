# Universal Rules

- Ein Heartbeat bearbeitet einen Task; kein Scope Creep.
- Vor Arbeit: Checkout, Rolle, Task, Grenzen, Verifikation, Budget und Kontext laden.
- KISS/YAGNI; vorhandene Muster vor neuen Abstraktionen; keine unbegründete Dependency.
- Jede Verhaltensänderung hat Test oder genehmigte konkrete Begründung.
- Keine Secrets, Produktionsdaten, lokalen Artefakte oder destruktiven Git-Aktionen.
- Fremde Änderungen bleiben erhalten.
- Doku folgt Verhalten, Architektur, Betrieb und Security.
- Status braucht Evidenz und nächsten Owner.
- Kein identischer Retry; Anti-Loop-Regeln gelten.
- Rollen und Modelle bleiben getrennt; Budget/Approval werden respektiert.

Unklarer Scope: `needs_refinement`; mehrere Ziele: `split_required`; externe Abhängigkeit: passender Blocker.
