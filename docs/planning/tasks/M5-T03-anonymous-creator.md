# Task M5-T03: Anonyme QR-Erstellung ohne Registrierung

```yaml
phase: M5
workflow_state: in_progress
goal: "Besucher können ohne Registrierung einen URL-QR-Code erstellen. Nach Erstellung wird Conversion zur Registrierung gefördert."
spec_refs: ["Pflichtenheft §M5.3 (8A)"]
risk: medium
complexity: medium
dependencies: []
attempt_counter: 1
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create: `app/Livewire/AnonymousCreator.php`, `resources/views/livewire/anonymous-creator.blade.php`
- Modify: `routes/web.php`, `resources/views/landing.blade.php`
- Out of scope: Wasserzeichen-Rendering im QR-Bild, Post-Registration-Hook, 24h-Cleanup-Job

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Besucher ohne Login, URL-Input |
| Outputs/Side effects | QR-Code erstellt, 24h gültig, Gast-User angelegt |
| Edge cases | ungültige URL, leerer Titel |
| Errors | Validierungsfehler anzeigen, keine 500er |

## Offen (Teil-Tasks)

| Sub-Task | Status |
|---|---|
| Landing Page CTA + `/create` Route | ✅ Done |
| AnonymousCreator Livewire + View | ✅ Done |
| Conversion-Funnel nach Erstellung | ✅ Done |
| Wasserzeichen auf anonymen QRs | ❌ Offen → M5-T03a |
| 24h-Cleanup-Job für Gast-Codes | ❌ Offen → M5-T03b |
| Post-Registration: Code ins Konto übernehmen | ❌ Offen → M5-T03c |

## Akzeptanz

| Kriterium | Nachweis |
|---|---|
| `/create` funktioniert ohne Login | Browser: Seite lädt, QR wird erstellt |
| Conversion-Hinweis nach Erstellung | View zeigt Registrierungs-CTA |
| Code ist 24h gültig | `expires_at = now+24h` in DB |

## Routingrahmen

Required Skills: `laravel`, `livewire`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low.

## Result (partial)

```
Result: needs_refinement (3 Sub-Tasks offen)
Changed: AnonymousCreator.php, anonymous-creator.blade.php, web.php, landing.blade.php
Verification: /create funktioniert end-to-end im Browser
Attempts: 1
Risks: Anonyme Codes ohne Wasserzeichen sind Missbrauchsfläche
Next owner/state: Frontend UI Developer / M5-T03a-c
```