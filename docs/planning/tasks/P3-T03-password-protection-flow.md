# Task P3-T03: Passwortschutz-Flow für QR-Codes härten

```yaml
phase: P3
workflow_state: task_review
goal: "Der Passwortschutz für QR-Codes ist UX-seitig klar, fehlerarm und durch Regressionstests abgesichert."
spec_refs: ["Pflichtenheft §9.2", "Pflichtenheft §9.4", "Pflichtenheft §7.3"]
risk: medium
complexity: medium
dependencies: ["bestehende QR-Resolver- und Detailseiten", "Passwort-Hash/Grant-Flow"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: Passwort-Formular, Resolver-Edge-Cases, Fehlermeldungen, Tests
- Out of scope: neues Auth-System, zusätzliche Crypto-Provider, UI-Neudesign der gesamten App

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | QR-Code mit Passwortschutz, Nutzer gibt Passwort ein |
| Outputs/Side effects | korrekt freigeschalteter Resolver-Flow oder klare Fehlermeldung |
| Edge cases | falsches Passwort, leeres Passwort, abgelaufener Code, Burn/max_scans |
| Errors | keine Passwortleaks in URLs, Logs oder Fehlermeldungen |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Passwortgeschützte QR-Codes sind aufrufbar nach erfolgreicher Freischaltung | Feature | resolver/password tests |
| Falsche Passwörter bleiben gesperrt und erzeugen keine Side Effects | Feature | negative tests |
| UX zeigt klare Hinweise und keine Roh-Exceptions | Browser/Feature | Smoke-Test auf der Resolver-Seite |

## Routingrahmen

Required Skills: `task-definition`, `laravel`, `testing`, `security`; Candidate Role: Backend Domain Developer; Minimum/Preferred Tier: standard; Allowed Classes: medium; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
```