# Task FEAT-10-E: Rate-Limit-Header und Analytics-API

> Zweck: Transparente Rate-Limits via Header und Scan-Statistiken über die API abrufbar.

```yaml
phase: P4
workflow_state: task_review
goal: "API-Antworten enthalten Rate-Limit-Header; Analytics-Endpoints liefern Scan-Stats"
spec_refs: ["FEAT-10.4", "FEAT-10.5", "§3.3"]
risk: low
complexity: medium
dependencies: ["FEAT-10-C"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create: `app/Http/Middleware/ApiRateLimitHeaders.php`
- Create: `app/Http/Controllers/Api/QrCodeStatsController.php`
- Modify: `routes/api.php` (Stats-Routen + Middleware auf API-Gruppe)
- Create: `tests/Feature/ApiRateLimitHeaderTest.php`
- Create: `tests/Feature/ApiStatsTest.php`
- Out of scope: Aggregation-Jobs (existieren bereits)

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Rate-Limit-Header | `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset` auf jeder API-Antwort |
| 429-Response | Zusätzlich `Retry-After` Header |
| GET /api/qr-codes/{id}/stats | Gesamtstatistiken + letzte 20 Scans, Auth: Token+stats:read |
| GET /api/qr-codes/{id}/stats/daily | Tägliche Aggregationen |
| GET /api/qr-codes/{id}/stats/hourly | Stündliche Aggregationen |
| Rate pro Tarif | Pro 60/Min, Business 300/Min |
| Errors | 429 bei Limit, 403 bei fehlendem Scope |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Header vorhanden | `test_rate_limit_headers_present`, feature | `php artisan test tests/Feature/ApiRateLimitHeaderTest.php` |
| 429 bei Überschreitung | `test_rate_limit_returns_429`, feature | dto. |
| Stats zurückgegeben | `test_stats_endpoint_returns_data`, feature | `php artisan test tests/Feature/ApiStatsTest.php` |
| Daily-Stats korrekt | `test_daily_stats_aggregated`, feature | dto. |
| Stats brauchen stats:read | `test_stats_require_scope`, feature | dto. |

## Routingrahmen

Required Skills: Laravel, Redis, Rate-Limiting. Candidate Role: Backend Dev. Tier: Pro/Business. Complexity: medium.
