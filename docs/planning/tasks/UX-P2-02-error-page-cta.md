# Task UX-P2-02: Error-Pages mit Handlungsoptionen erweitern

```yaml
phase: P6
workflow_state: task_review
goal: "QR-Fehlerseiten (Not Found / Expired / Burned / Max Scans) bieten konkrete CTAs: Zur Startseite, Support kontaktieren, QR-Code-Hilfe."
spec_refs: ["Pflichtenheft §3.5 (Resolver-Fehlerseiten)"]
risk: low
complexity: low
dependencies: []
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `resources/views/qr-types/error.blade.php`, `resources/views/qr-types/layout.blade.php`
- Out of scope: Neue Error-Typen, Logging-Änderungen

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Öffentlicher Besucher scannt ungültigen/abgelaufenen Code |
| Outputs/Side effects | Seite zeigt Icon + Grund + 2 CTAs (Startseite, Hilfe) + ggf. Support-Link |
| Edge cases | Mobile: CTAs vertikal gestapelt |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| 2+ CTAs auf Error-Seite | Browser | `/r/INVALID` zeigt 2 Buttons |
| CTAs sind symmetrisch ausgerichtet | Browser | Bounding-Box-Check |
| Fehlergrund klar verständlich | Browser | Titel + Beschreibungstext sinnvoll |

## Routingrahmen

Required Skills: `blade`, `tailwindcss`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
