# Task UX-P2-05: API-Dokumentation mit Beispielen

```yaml
phase: P6
workflow_state: task_review
goal: "API-Index-Seite zeigt Request/Response-Beispiele je Endpunkt, Auth-Beschreibung (Bearer Token) und Scope-Tabelle."
spec_refs: ["Pflichtenheft §6.3 (REST-API)"]
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

- Modify: `app/Http/Controllers/ApiIndexController.php` (Beispiel-Daten), `resources/views/api/index.blade.php`
- Out of scope: OpenAPI/Swagger-Integration (separate Task FEAT-10-D), neue Endpunkte

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Öffentlicher Besucher oder Entwickler öffnet `/api` |
| Outputs/Side effects | JSON/HTML zeigt pro Endpunkt: Method, Path, Auth, Scopes, Beispiel-Request, Beispiel-Response |
| Edge cases | Keine Auth → Beispiel ohne Token; Auth-Endpunkt → Beispiel mit `Authorization: Bearer ...` |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Jeder Endpunkt hat Beispiel | Feature | `php artisan test --filter=ApiIndexControllerTest` |
| Auth-Beschreibung sichtbar | Browser | Snapshot zeigt Bearer-Token-Hinweis |
| Beispiel-Response ist gültiges JSON | Unit | JSON-Schema-Check im Test |

## Routingrahmen

Required Skills: `laravel`, `blade`, `api-design`; Candidate Role: Backend Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
