# Task [ID]: [ACTIONABLE TITLE]

> Zweck: Produktionsauftrag für einen fokussierten Lauf. Ausfüllen mit konkreten Pfaden, Verhalten und Nachweisen.

```yaml
phase: P#
workflow_state: task_review
goal: "[ein beobachtbares Ergebnis]"
spec_refs: ["FR-...", "AC-..."]
risk: low|medium|high|critical
complexity: low|medium|high
dependencies: []
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify/Delete: `[konkrete Pfade]`
- Out of scope: `[explizite Grenzen]`

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | `[data/state]` |
| Outputs/Side effects | `[result]` |
| Edge cases | `[boundaries]` |
| Errors | `[observable behavior]` |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| `[Given/When/Then]` | `[test, unit/integration/e2e]` | `[command]` |

## Routingrahmen

Required Skills, Candidate Role, Minimum/Preferred Tier, Allowed Classes, Max Cost, Premium Allowed/Reason, Fallback.

## Definition of Ready

- [ ] Spec/Bedarfsanalyse fertig; ein fachliches Ziel; Scope und Nicht-Ziele konkret.
- [ ] Höchstens ein primäres Layer; Abhängigkeiten/Blocker modelliert.
- [ ] Verhalten, Akzeptanz, Tests und reale Befehle vollständig.
- [ ] Security/Doku/Release-Auswirkungen markiert; Routingrahmen plausibel.

Reviewer setzt `ready`, `needs_refinement` oder `split_required`.
