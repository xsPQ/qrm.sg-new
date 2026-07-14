# Task UX-P3-03: QR-Type Filter-Dropdown bereinigen

```yaml
phase: P6
workflow_state: task_review
goal: "Dashboard-Typ-Filter zeigt nur die 8 echten QR-Typen (URL, Message, Redirect, Social, WiFi, Crypto, Event, Contact) — entfernt irrelevante Einträge (Text, Email, Phone, SMS, vCard) die keine Creator-Optionen sind."
spec_refs: ["Pflichtenheft §3.2 (QR-Typen)"]
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

- Modify: `app/Livewire/Dashboard.php` (Typ-Filter-Liste), `resources/views/livewire/dashboard.blade.php`
- Out of scope: QR-Type-Erweiterungen, Creator-Änderung

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Nutzer öffnet Dashboard-Filter |
| Outputs/Side effects | Dropdown zeigt exakt die 8 QR-Typen aus dem Creator |
| Edge cases | Alte Codes mit nicht mehr unterstützen Typen → unter "Other" gruppiert oder ausgeblendet |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Genau 8 Typen im Filter | Browser | Snapshot: Dropdown hat 8+All |
| Keine Phantom-Typen | Browser | Kein "Text", "Email", "Phone", "SMS" |

## Routingrahmen

Required Skills: `laravel`, `livewire`; Candidate Role: Frontend Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
