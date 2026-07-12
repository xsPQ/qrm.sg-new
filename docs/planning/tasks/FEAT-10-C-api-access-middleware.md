# Task FEAT-10-C: API-Berechtigungsprüfung (Pro-Add-on / Business)

> Zweck: API-Token-Erstellung und -Nutzung erfordert Pro+Add-on oder Business. Free und Pro-ohne-Add-on werden blockiert.

```yaml
phase: P4
workflow_state: task_review
goal: "Token-basierte API nutzt nur Nutzer mit API-Berechtigung (Business oder Pro+Add-on)"
spec_refs: ["FEAT-10.6", "§2.2", "§2.3"]
risk: medium
complexity: high
dependencies: ["FEAT-10-A"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create: `app/Http/Middleware/CheckApiAccess.php`
- Modify: `app/Livewire/ApiTokenManager.php` (Token-Erstellung prüft Berechtigung)
- Modify: `routes/api.php` (Middleware auf auth:sanctum-Gruppe)
- Modify: Stripe-Produkt-Konfiguration: neues Produkt `pro_api_addon` (+1 EUR/Monat)
- Create: `tests/Feature/ApiAccessTest.php`
- Out of scope: Stripe-Checkout-Flow für das Add-on (Separater Task)

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Bearer-Token im Authorization-Header |
| Free-Nutzer mit Token | 403 `{ message, code: "API_ACCESS_DENIED" }` |
| Pro ohne Add-on | 403 mit Upgrade-Hinweis auf API-Add-on |
| Pro mit Add-on | Token funktioniert, Scopes werden geprüft |
| Business | Token funktioniert mit allen Scopes |
| Edge cases | Grandfathered Tokens von downgraded Accounts werden deaktiviert |
| Errors | 403 strukturiert gemäß FEAT-10.7 |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Free mit Token → 403 | `test_free_user_api_access_denied`, feature | `php artisan test tests/Feature/ApiAccessTest.php` |
| Business mit Token → 200 | `test_business_user_api_access_granted`, feature | dto. |
| Pro ohne Add-on → 403 | `test_pro_without_addon_denied`, feature | dto. |
| Pro mit Add-on → 200 | `test_pro_with_addon_granted`, feature | dto. |

## Routingrahmen

Required Skills: Laravel, Sanctum, Stripe/Cashier. Candidate Role: Backend Dev. Tier: Pro/Business. Complexity: high.
