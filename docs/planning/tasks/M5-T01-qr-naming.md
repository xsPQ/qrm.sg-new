# Task M5-T01: QR-Code Naming & Dashboard-Anker

```yaml
phase: M5
workflow_state: done
goal: "Dashboard, Detail-Seite und Creator nutzen den QR-Code-Namen als primäre Identifikation, nicht den Kurzcode."
spec_refs: ["Pflichtenheft §M5.1 (8A)"]
risk: low
complexity: low
dependencies: []
attempt_counter: 1
failure_reason: null
current_owner: null
next_allowed_state: done
escalation_target: Task Reviewer
```

## Scope

- Modify: `resources/views/livewire/qr-code-list.blade.php`, `resources/views/qr-codes/show.blade.php`, `app/Livewire/QrCreator.php`
- Out of scope: Datenmodell-Änderung (title existiert bereits), neue Migration

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | QR-Code mit `title`-Feld |
| Outputs/Side effects | Dashboard: Name als erste Spalte, Code/Alias darunter |
| Edge cases | QR ohne title → Fallback auf Code/Alias |
| Errors | keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Dashboard zeigt Namen primär | Browser/Feature | `/dashboard` zeigt title in erster Spalte |
| Detail-Seite: Name als h1 | Browser | `/qr-codes/{id}` zeigt title als Überschrift |
| Creator: title required | Feature | Livewire-Validierung blockt leeren title |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`; Candidate Role: Frontend UI Developer; Minimum/Preferred Tier: standard; Allowed Classes: low; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.

## Result

```
Result: done
Changed: qr-code-list.blade.php, show.blade.php
Verification: /dashboard zeigt Namen primär; /qr-codes/{id} zeigt title als h1
Attempts: 1
Budget used: standard
Risks: none
Next owner/state: Task Reviewer / done
```