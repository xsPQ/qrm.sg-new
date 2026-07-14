# Task UX-P1-04: Account/Billing in Tabs aufteilen

```yaml
phase: P6
workflow_state: task_review
goal: "Account-Seite wird in logische Tabs aufgeteilt: Profil, Sicherheit, Abrechnung, Team, Entwickler (API + Bulk), Danger Zone — statt alles auf einer langen Seite."
spec_refs: ["Pflichtenheft §3.4 (Account-Verwaltung)"]
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

- Modify: `resources/views/account.blade.php` (oder entsprechende Blade-View), ggf. `app/Livewire/Account.php`
- Out of scope: Neue Features, Stripe-Integration-Änderungen

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Eingeloggter Nutzer öffnet `/account` |
| Outputs/Side effects | Seite zeigt 5-6 Tabs: Profil · Sicherheit · Abrechnung · Team · Entwickler · Konto löschen |
| Edge cases | Free-Nutzer: Team-Tab deaktiviert mit Upgrade-Hinweis; Business: alle Tabs aktiv |
| Errors | Tab-Wechsel ohne Page-Reload (Livewire/Alpine) |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| 5+ Tabs sichtbar | Browser | Snapshot zeigt Tab-Navigation |
| Nur aktiver Tab zeigt Inhalt | Browser | Wechsel zwischen Tabs aktualisiert Inhalt |
| Team-Tab für Free gesperrt | Feature | `php artisan test --filter=AccountPageTabTest` |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`, `alpinejs`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
