# Task M5-T02: Analytics-Sichtbarkeit im Dashboard

```yaml
phase: M5
workflow_state: done
goal: "Scan-Anzahl im Dashboard ist klickbar und führt zur Analytics-Seite; Analytics-Icon pro Zeile."
spec_refs: ["Pflichtenheft §M5.2 (8A)"]
risk: low
complexity: low
dependencies: ["M5-T01"]
attempt_counter: 1
failure_reason: null
current_owner: null
next_allowed_state: done
escalation_target: Task Reviewer
```

## Scope

- Modify: `resources/views/livewire/qr-code-list.blade.php`
- Out of scope: Analytics-Seite selbst (bereits vorhanden unter `/qr-codes/{id}/analytics`)

## Akzeptanz

| Kriterium | Nachweis |
|---|---|
| Scan-Zahl klickbar | `<a href="...analytics">` um `scan_count` |
| Analytics-Icon pro Zeile | SVG-Icon in Actions-Spalte, verlinkt auf analytics |

## Result

```
Result: done
Changed: qr-code-list.blade.php
Verification: /dashboard, Scan-Zahl ist Link, Icon sichtbar
Attempts: 1
Risks: none
```