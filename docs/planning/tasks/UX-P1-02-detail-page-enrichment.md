# Task UX-P1-02: Detailseite mit Metadaten anreichern

```yaml
phase: P6
workflow_state: task_review
goal: "QR-Code-Detailseite zeigt zusätzliche Metadaten: Status-Badge, Ziel-URL/Auszug, Erstellungsdatum, letzter Scan, Scan-Verlauf-Sparkline oder -Zahl, Route-Code mit Copy-Button."
spec_refs: ["Pflichtenheft §3.3 (Detailansicht)"]
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

- Modify: `resources/views/qr-codes/show.blade.php`, `app/Livewire/QrCodeDetail.php` (oder entsprechender Controller)
- Out of scope: Analytics-Detailseite mit Charts, neue API-Endpunkte
- Achtung: Livewire-Komponente prüfen — ggf. `app/Http/Controllers/QrCodeController.php@show`

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Eingeloggter Nutzer öffnet `/qr-codes/{id}` |
| Outputs/Side effects | Seite zeigt: QR-Preview, Download SVG/PNG, Status-Badge, Route-Code+Copy, Ziel/Auszug, Scans gesamt, letzter Scan |
| Edge cases | Nie gescannter Code → "Noch keine Scans"; abgelaufener Code → Badge rot |
| Errors | Fremder Code → 403 |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Status-Badge sichtbar | Browser | Snapshot zeigt Badge |
| Route-Code mit Copy-Button | Browser | Copy-Button funktional |
| Letzter Scan angezeigt | Feature | `php artisan test --filter=QrCodeDetailTest` |
| Ziel-URL bei URL-Type angezeigt | Browser | URL im Content-Bereich sichtbar |

## Routingrahmen

Required Skills: `laravel`, `blade`, `livewire`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
