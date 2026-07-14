# Task FEAT-10-A: QR-Typ-Schema-Endpoint (`GET /api/qr-types`)

> Zweck: Ein KI-Agent oder externes System kann alle 8 QR-Typen mit Felddefinitionen abrufen.

```yaml
phase: P4
workflow_state: task_review
goal: "GET /api/qr-types liefert alle QR-Typen mit Schemata, öffentlich ohne Auth"
spec_refs: ["FEAT-10.2", "§3.1.3"]
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

- Create: `app/Http/Controllers/Api/QrTypeController.php`
- Modify: `routes/api.php` (Route hinzufügen)
- Create: `tests/Feature/QrTypeSchemaTest.php`
- Out of scope: Auth, Rate-Limiting, OpenAPI-Doku

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | GET-Request, keine Auth nötig |
| Outputs/Side effects | JSON mit `types[]` Array, pro Typ: id, label, description, icon, fields[] |
| Edge cases | Leerfield options werden als leeres Objekt zurückgegeben |
| Errors | 500 bei Serverfehler |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Response enthält 8 Typen | `test_qr_types_returns_all_eight_types`, feature | `php artisan test tests/Feature/QrTypeSchemaTest.php` |
| Felder haben korrekte Typen | `test_fields_have_correct_types`, feature | dto. |
| Select-Felder haben Options | `test_select_fields_have_options`, feature | dto. |
| Endpoint benötigt keine Auth | `test_qr_types_accessible_without_token`, feature | dto. |

## Routingrahmen

Required Skills: Laravel, PHP. Candidate Role: Backend Dev. Tier: any. Complexity: medium.
