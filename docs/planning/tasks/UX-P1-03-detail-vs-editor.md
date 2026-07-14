# Task UX-P1-03: Detail vs. Editor visuell trennen

```yaml
phase: P6
workflow_state: task_review
goal: "Detailseite und Editorseite sind visuell und funktional klar unterscheidbar: Detail = Lese-Modus mit klarem 'Bearbeiten'-CTA; Editor = Formular-Modus mit klarem 'Zurück zur Detailansicht'-Link."
spec_refs: ["Pflichtenheft §3.3 (Detailansicht vs. Editor)"]
risk: low
complexity: low
dependencies: ["UX-P1-02"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `resources/views/qr-codes/show.blade.php` (Lese-Badge/Header), `resources/views/qr-codes/edit.blade.php` (Bearbeitungs-Badge/Header)
- Out of scope: Funktionsänderungen, neues Routing

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Nutzer wechselt zwischen `/qr-codes/{id}` und `/qr-codes/{id}/edit` |
| Outputs/Side effects | Detail: grau/neutraler Header mit "View"-Indikator; Editor: gelber/blauer Header mit "Editing"-Indikator |
| Edge cases | Mobile: Indikator als Text-Badge statt Farbunterschied |
| Errors | Keine |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Header-Unterschied sichtbar | Browser | Snapshot: unterschiedlicher Modus-Indikator |
| "Edit"-Button prominent auf Detailseite | Browser | `ref` für Edit-Button vorhanden |
| "View details"-Link prominent im Editor | Browser | Snapshot zeigt Zurück-Link |

## Routingrahmen

Required Skills: `blade`, `tailwindcss`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: low; Premium Allowed: false.
