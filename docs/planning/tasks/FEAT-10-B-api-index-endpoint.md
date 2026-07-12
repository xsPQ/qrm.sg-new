# Task FEAT-10-B: API-Index-Endpoint (`GET /api`)

> Zweck: Kompakte Übersicht aller API-Endpunkte für Auto-Discovery.

```yaml
phase: P4
workflow_state: task_review
goal: "GET /api liefert strukturierte Endpunkt-Übersicht ohne Auth"
spec_refs: ["FEAT-10.3"]
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

- Create: `app/Http/Controllers/Api/ApiIndexController.php`
- Modify: `routes/api.php`
- Create: `tests/Feature/ApiIndexTest.php`
- Out of scope: OpenAPI-Generierung

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | GET `/api`, keine Auth |
| Outputs/Side effects | JSON: `{ name, version, endpoints[] }` mit method, path, description, auth_required, scopes[] |
| Edge cases | — |
| Errors | — |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Response hat Endpunkte | `test_api_index_returns_endpoints`, feature | `php artisan test tests/Feature/ApiIndexTest.php` |
| Enthält qr-codes CRUD | `test_api_index_contains_qr_crud`, feature | dto. |
| Enthält qr-types | `test_api_index_contains_qr_types`, feature | dto. |

## Routingrahmen

Required Skills: Laravel, PHP. Candidate Role: Backend Dev. Tier: any. Complexity: low.
