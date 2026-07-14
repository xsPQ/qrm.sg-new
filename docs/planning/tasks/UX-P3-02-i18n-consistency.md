# Task UX-P3-02: i18n-Konsistenz (gemischte EN/DE-Strings)

```yaml
phase: P6
workflow_state: task_review
goal: "Alle UI-Texte sind konsequent deutsch bei deutscher Spracheinstellung: Pagination-Labels, Forgot-Password-Text, Button-Labels."
spec_refs: ["Pflichtenheft §8.1 (i18n DE/EN)", "Task P3-T02"]
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

- Modify: `lang/de.json`, `lang/en.json`, Blade-Views mit hartkodierten Strings
- Out of scope: Neue Sprachen, Backend-Fehlermeldungen

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | App-Sprache ist DE |
| Outputs/Side effects | Alle sichtbaren Texte auf Deutsch |
| Edge cases | EN-Modus: alle Texte auf Englisch |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| "Next »" → "Weiter" | Browser | Dashboard-Pagination auf DE |
| Forgot-Password-Text auf DE | Browser | `/forgot-password` Snapshot |
| Button-Labels einheitlich DE/EN | Feature | `php artisan test --filter=I18nConsistencyTest` |

## Routingrahmen

Required Skills: `laravel`, `i18n`, `blade`; Candidate Role: Frontend Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
