# Task UX-P1-01: Dashboard-Übersichtsstatistiken

```yaml
phase: P6
workflow_state: task_review
goal: "Dashboard zeigt kompakte Statistik-Karten oberhalb der Tabelle: Gesamte Codes, Aktive Codes, Scans heute, Bald ablaufend."
spec_refs: ["Pflichtenheft §3.3 (Dashboard)"]
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

- Modify: `app/Livewire/Dashboard.php` (Berechnung der Metriken), `resources/views/livewire/dashboard.blade.php`
- Create: Statistik-Karten-Komponente
- Out of scope: Analytics-Detailseite, Chart-Bibliothek

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Eingeloggter Nutzer öffnet `/dashboard` |
| Outputs/Side effects | 4 Statistik-Karten (Total / Active / Scans Today / Expiring ≤7d) oberhalb der Tabelle |
| Edge cases | Free-Nutzer mit 0 Codes → alle Karten zeigen 0; Large Dataset → performant durch COUNT-Queries |
| Errors | Keine — rein read-only |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| 4 Stat-Karten sichtbar | Feature/Dusk | Browser: Karten haben Daten |
| Werte korrekt für Test-User | Unit | `php artisan test --filter=DashboardStatsTest` |
| Performance < 200ms mit 100k Codes | Benchmark | `curl -w '%{time_total}' http://qrm.sg/dashboard` |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
