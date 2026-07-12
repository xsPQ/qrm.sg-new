# Task FEAT-10-D: OpenAPI/Scribe-Dokumentation

> Zweck: Die gesamte API ist maschinenlesbar dokumentiert. Ein KI-Agent kann die API via `/api/openapi.json` selbstständig entdecken.

```yaml
phase: P4
workflow_state: task_review
goal: "GET /api/openapi.json liefert OpenAPI 3.1 Spec; GET /api/docs liefert HTML-Doku"
spec_refs: ["FEAT-10.1"]
risk: low
complexity: medium
dependencies: ["FEAT-10-A", "FEAT-10-B", "FEAT-10-C"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Install: `knuckleswtf/scribe` (Laravel-Paket) via Composer
- Configure: `config/scribe.php`
- Modify: Controller-Annotationen (`@group`, `@bodyParam`, `@response` etc.)
- Create: Routes `GET /api/docs` und `GET /api/openapi.json`
- Out of scope: API-Logik-Änderungen

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | GET `/api/openapi.json`, keine Auth |
| Outputs/Side effects | OpenAPI 3.1 JSON mit allen Endpunkten, Schemata, Beispielen |
| GET /api/docs | Gerenderte HTML-Doku (Scribe-Default) |
| Edge cases | Public und auth-Endpunkte korrekt markiert |
| Errors | — |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| openapi.json ist gültiges JSON | `test_openapi_json_is_valid`, feature | `php artisan test --filter=OpenApi` |
| Enthölt qr-codes Endpunkte | `test_openapi_contains_qr_crud`, feature | dto. |
| HTML-Doku erreichbar | `test_api_docs_html_accessible`, feature | dto. |

## Routingrahmen

Required Skills: Laravel, OpenAPI, Scribe. Candidate Role: Backend Dev. Tier: any. Complexity: medium.
