# Task P2-T01: Availability-Eingabe validieren
```yaml
workflow_state: ready
goal: "Ungültige Status-, Rückkehrzeit- und Notizwerte deterministisch abweisen."
spec_refs: [FR-003, FR-004, AC-004]
risk: medium
complexity: low
dependencies: []
attempt_counter: 0
next_allowed_state: routed
```
Scope: create `src/domain/validateAvailability.ts`, `tests/domain/validateAvailability.test.ts`. Out: API, DB, UI, Auth.

Input `{state, returnAt?, note?}` + now; Output normalisiertes Value Object. Nur drei States; returnAt nur away/zukünftig; Note trim/max120; typisierter ValidationError ohne Loginhalt.

Tests: valid away, vergangene Zeit, unbekannter State, Note 121, Trim, returnAt bei focus. Commands: `npm test -- validateAvailability`, `npm run typecheck`.

Routing: Backend Domain Developer; TypeScript/Tests; min B/preferred A; subscription/paid_low; max 0,40; Premium false. Review pass: ein Ziel, zwei Dateien, klare Tests.
