# Task M5-T06: A/B Testing — Editor-UI + Analytics-Erweiterung

```yaml
phase: M5
workflow_state: ready
goal: "Im QR-Editor können Pro+ Nutzer Varianten (A/B/C) anlegen, mit Strategie (Random/Device). Analytics zeigt pro-Variante Metriken."
spec_refs: ["Pflichtenheft §M5.6 (8A)"]
risk: medium
complexity: medium
dependencies: ["qr_code_variants Migration (fertig)", "VariantSelector (fertig)", "M5-T01"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `app/Livewire/QrCodeEditor.php`, Editor-Blade, `app/Livewire/QrCodeAnalytics.php`
- Create: Varianten-Formular im Editor (Label, URL, Weight, Device-Target), Strategie-Auswahl
- Out of scope: Resolver-Logik (fertig), Migration (fertig)

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Pro+ Nutzer | Sieht „A/B Testing" Tab/Sektion im Editor |
| Free Nutzer | Sieht Locked-State mit Upgrade-Hinweis |
| Varianten speichern | `QrCodeService::syncVariants()` |
| Analytics | Zeigt Tabelle: Variante | Scans | Anteil |

## Akzeptanz

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Editor kann 2+ Varianten anlegen | Feature | syncVariants wird aufgerufen, DB enthält Einträge |
| Strategie wählbar (Random/Device) | Feature | settings.ab_testing.strategy gesetzt |
| Free-Nutzer sieht Locked-State | Feature | assertAbTesting blockiert |
| Analytics zeigt pro-Variante Scan-Zahlen | Feature | Variant-Tabelle in Analytics-View |

## Offene Sub-Tasks

| Sub-Task | Status |
|---|---|
| Editor-UI für Varianten-Verwaltung | ❌ Offen |
| Strategie-Auswahl-Dropdown | ❌ Offen |
| Analytics: Varianten-Tabelle | ❌ Offen |
| Backend (Selector, Resolver, Migration) | ✅ Fertig |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`; Candidate Role: Frontend UI Developer; Tier: standard; Max Cost: medium.

```
Result: not started
```