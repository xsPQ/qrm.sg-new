# Task UX-P3-01: Status-Badges als farbige Pills

```yaml
phase: P6
workflow_state: task_review
goal: "Status-Anzeige im Dashboard (Active/Expired/Burned) wird von reinem Text zu farbigen Badge-Pills: Active=grün, Expired=orange, Burned=rot."
spec_refs: ["WCAG 1.4.1 (Farbe als Enhancement, nicht alleiniges Mittel)"]
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

- Modify: `resources/views/livewire/dashboard.blade.php` (Status-Zelle)
- Out of scope: Filter-Logik, API-Änderung

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Dashboard-Tabelle wird gerendert |
| Outputs/Side effects | Status-Zelle zeigt Pill-Badge mit Farbe+Text |
| Edge cases | Farbkontrast WCAG-konform; Text bleibt lesbar (nicht nur Farbe) |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Active=grün, Expired=orange, Burned=rot | Browser | CSS-Klassen prüfen |
| Text bleibt enthalten | Browser | Pill zeigt Text "Active" etc. |

## Routingrahmen

Required Skills: `blade`, `tailwindcss`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
