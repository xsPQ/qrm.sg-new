# Task UX-P2-03: Landing Page vertrauensbildend erweitern

```yaml
phase: P6
workflow_state: task_review
goal: "Landing Page erhält Vergleichstabelle Free/Pro/Business, Screenshot-Mockups der App und Social-Proof-Bereich."
spec_refs: ["Pflichtenheft §3.1 (Landing Page)"]
risk: low
complexity: medium
dependencies: []
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `resources/views/welcome.blade.php` (oder `landing.blade.php`)
- Create: Vergleichstabelle-Komponente, Mockup-Bilder/Placeholders
- Out of scope: Echte Testimonials, Blog-System

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Öffentlicher Besucher öffnet `/` |
| Outputs/Side effects | Seite zeigt zusätzlich: Vergleichstabelle, Screenshots/Mockups, Trust-Badges |
| Edge cases | Mobile: Tabelle wird zu Akkordeon |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Vergleichstabelle mit 3 Plänen sichtbar | Browser | Snapshot zeigt Tabelle |
| Screenshot/Mockup sichtbar | Browser | `img`-Tag vorhanden |
| Mobile: Tabelle umbricht sauber | Browser | Responsive-Check |

## Routingrahmen

Required Skills: `blade`, `tailwindcss`, `design`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
