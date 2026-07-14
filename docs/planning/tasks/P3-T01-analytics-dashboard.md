# Task P3-T01: Analytics-Dashboard mit freien Standard-Tools

```yaml
phase: P3
workflow_state: task_review
goal: "Ein nutzerfreundliches Analytics-Dashboard mit vorhandenen freien Bausteinen (Filament Widgets + Chart.js) anzeigen, ohne eine eigene Chart-/BI-Lösung zu bauen."
spec_refs: ["Pflichtenheft §12.6", "Pflichtenheft §9.4", "Pflichtenheft §7.6"]
risk: medium
complexity: medium
dependencies: ["bestehende Aggregationsjobs und Scan-Daten"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: `app/Filament/Widgets/*`, ggf. `app/Livewire/*`, `resources/views/*`, `tests/Feature/*Analytics*`
- Out of scope: neue BI-Engine, externe SaaS-Analytics, zusätzliche Lizenzabhängigkeiten

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | vorhandene Scan- und Aggregationsdaten, eingeloggter Nutzer mit Zugriff auf eigene Codes |
| Outputs/Side effects | KPI-Kacheln, Zeitreihe, Top-Länder/Geräte, Filter nach Zeitraum |
| Edge cases | keine Scans, nur alte Scans, leere Filter, großer Zeitraum |
| Errors | leere Zustände verständlich anzeigen; keine 500er bei fehlenden Daten |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Dashboard zeigt Scan-KPIs und eine Zeitreihe | Feature/Livewire | `php artisan test --filter=Analytics` |
| Filter ändern die angezeigten Werte deterministisch | Feature | Test mit Zeitraum- und Datensatz-Varianten |
| Leere Daten liefern eine brauchbare Empty-State-Ansicht | Feature | Test für Nutzer ohne Scans |

## Routingrahmen

Required Skills: `task-definition`, `laravel`, `filament`, `testing`; Candidate Role: Frontend UI Developer; Minimum/Preferred Tier: standard; Allowed Classes: medium; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
```