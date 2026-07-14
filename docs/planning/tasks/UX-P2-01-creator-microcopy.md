# Task UX-P2-01: Creator/Editor Microcopy ergänzen

```yaml
phase: P6
workflow_state: task_review
goal: "Felder im Creator und Editor erhalten kurze Inline-Hilfetexte (tooltips/hints), die erklären was sie tun: Max Scans, Burn, Passwort, Custom Alias, Error Correction, Margin."
spec_refs: ["Pflichtenheft §3.2 (Creator)", "WCAG 3.3.2"]
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

- Modify: `resources/views/livewire/qr-creator.blade.php`, `resources/views/livewire/qr-code-editor.blade.php`
- Create: Tooltip/Hint-Komponente oder Inline-Text mit `aria-describedby`
- Out of scope: Neue Felder, Layout-Umbruch

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Nutzer öffnet Creator oder Editor |
| Outputs/Side effects | Jedes komplexe Feld hat einen kurzen Erklärungstext (1 Satz) darunter oder als Tooltip |
| Edge cases | Texte auf DE und EN verfügbar (i18n) |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Max Scans hat Hint | Browser | Snapshot zeigt Hinweistext |
| Burn hat Hint | Browser | dito |
| Passwort hat Hint | Browser | dito |
| Hints i18n-fähig | Unit | `lang/de.json` und `lang/en.json` enthalten Keys |

## Routingrahmen

Required Skills: `blade`, `tailwindcss`, `i18n`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
