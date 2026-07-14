# Task M5-T04: Visuelle QR-Anpassung — Design-Tab UI

```yaml
phase: M5
workflow_state: ready
goal: "Creator und Editor erhalten einen Design-Tab, in dem Nutzer Farben, Dot-Stile, Gradient, Logo und Error-Correction einstellen können — abhängig vom Plan."
spec_refs: ["Pflichtenheft §M5.4 (8A)"]
risk: medium
complexity: high
dependencies: ["QrStyleService (fertig)", "M5-T01"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `app/Livewire/QrCreator.php`, `app/Livewire/QrCodeEditor.php`, korrespondierende Blade-Views
- Create: Design-Tab UI (Color-Picker, Select für Dot-Style, File-Upload für Logo)
- Out of scope: Änderungen an `QrStyleService` (fertig), neue Migration

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | Eingeloggter Nutzer; `featureFlags` vom EntitlementGate |
| Outputs/Side effects | `settings.style` JSON wird geschrieben; Live-Preview aktualisiert |
| Edge cases | Free-Nutzer sieht gesperrte Optionen mit Upgrade-Hinweis |
| Errors | Logo-Upload-Fehler, ungültige Farbe → klare Fehlermeldung |

## Akzeptanz

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Design-Tab sichtbar im Creator | Browser | Tab „Design" klickbar |
| Farbe änderbar (alle Pläne) | Feature | style.fg_color gesetzt |
| Gradient + Logo nur für Pro+ | Feature | Free → gesperrt, Pro → freigegeben |
| Live-Preview aktualisiert bei Änderung | Browser | Canvas/Bild reagiert |
| Gespeicherter Style wird beim Download angewandt | Feature | SVG/PNG enthält Farbe/Dots |

## Offene Sub-Tasks

| Sub-Task | Status |
|---|---|
| `QrStyleService` (Backend) | ✅ Fertig |
| Color-Picker + Dot-Select in Creator | ❌ Offen |
| Logo-Upload Komponente | ❌ Offen |
| Plan-Gating in der UI (Locked-State) | ❌ Offen |
| Live-Preview Integration | ❌ Offen |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`, `frontend`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: medium; Premium Allowed/Reason: false.

```
Result: not started
Risks: Live-Preview benötigt evtl. Client-Side QR-Rendering
```