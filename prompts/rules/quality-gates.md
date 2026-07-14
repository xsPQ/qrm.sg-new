# Quality Gates

1. Ready: Spec + Bedarf fertig; Task atomar/reviewt/geroutet.
2. Implementation: Scope eingehalten; Verhalten/Doku umgesetzt.
3. Test: Task- und erforderliche Gesamtchecks grün.
4. QA: unabhängiger Vergleich Task, Diff, Evidenz.
5. Security: bei Trigger keine offenen Critical/High.
6. Release: Build, Changelog, Migration/Rollback, Approval.
7. Health: Version, Readiness, Smoke, Logs/Metriken.

Fehlende Checks nennen Grund, Risiko, Owner und Folge-Issue; sie gelten nicht als bestanden.
