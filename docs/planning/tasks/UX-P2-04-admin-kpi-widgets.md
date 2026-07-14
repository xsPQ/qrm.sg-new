# Task UX-P2-04: Admin-Dashboard KPI-Widgets

```yaml
phase: P6
workflow_state: task_review
goal: "Filament Admin-Dashboard zeigt KPI-Widgets: Gesamte QR-Codes, Aktive User (24h), Scans heute, Aktive Subscriptions — statt leerer Willkommensseite."
spec_refs: ["Pflichtenheft §6.1 (Admin-Dashboard)"]
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

- Modify: `app/Filament/Pages/Dashboard.php` (oder `app/Providers/Filament/AdminPanelProvider.php`)
- Create: Filament Widget-Klassen (`app/Filament/Widgets/StatOverview.php`)
- Out of scope: Neue Admin-Routen, Berechtigungssystem-Änderung

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Admin öffnet `/admin` |
| Outputs/Side effects | Dashboard zeigt 4+ Stat-Karten mit Live-Werten aus der DB |
| Edge cases | Leere DB → alle Werte 0; 100k+ Codes → Query performant |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| 4+ KPI-Widgets sichtbar | Browser | `/admin` Snapshot zeigt Stat-Karten |
| Werte korrekt | Unit | `php artisan test --filter=AdminDashboardWidgetTest` |
| Ladezeit < 500ms mit 100k Codes | Benchmark | `curl -w '%{time_total}' http://qrm.sg/admin` |

## Routingrahmen

Required Skills: `filament`, `laravel`, `php`; Candidate Role: Backend Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
