# Task UX-P0-01: QR-Type Content-Validierung sicherstellen

```yaml
phase: P6
workflow_state: task_review
goal: "Jeder QR-Type akzeptiert nur sein definiertes Content-Schema; der MassLoadTestSeeder erzeugt typ-korrekte Inhalte; der Resolver zeigt bei Schema-Mismatch eine sinnvolle Seite statt 410/leerer Seite."
spec_refs: ["Pflichtenheft §3.2 (QR-Typen)", "FR-QR-Content-Validation"]
risk: high
complexity: medium
dependencies: []
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `app/Services/QrCodeService.php` (Content-Validierung beim Create/Update), `database/seeders/MassLoadTestSeeder.php` (typ-korrekte Contents), `app/Services/QrCodeResolver.php` (Schema-Mismatch abfangen)
- Create: `app/Rules/QrContentTypeRule.php` (Validierungs-Rule pro Type)
- Out of scope: UI-Änderungen am Creator, neue QR-Typen

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | QR-Code wird erstellt oder aktualisiert mit `type` und `content` |
| Outputs/Side effects | Validierungsfehler bei Schema-Mismatch; Seeder erzeugt korrekte Daten |
| Edge cases | `message` mit `{"url":"..."}` wird abgewiesen; unbekannter Type mit beliebigem Content wird abgewiesen |
| Errors | 422 Validation mit klaren Feld-Fehlern zurückgegeben |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| message-Type akzeptiert nur message-Feld | Unit | `php artisan test --filter=QrContentTypeValidationTest` |
| url-Type akzeptiert nur url-Feld | Unit | dito |
| Seeder erzeugt typ-korrekte Inhalte | Integration | `php artisan db:seed --class=MassLoadTestSeeder --force` dann DB-Check |
| Resolver zeigt sinnvolle Seite bei Mismatch | Feature | curl `/r/{code}` mit kaputtem Content → 200 mit Hinweis, nicht 410 |

## Routingrahmen

Required Skills: `laravel`, `php`, `testing`; Candidate Role: Backend Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
