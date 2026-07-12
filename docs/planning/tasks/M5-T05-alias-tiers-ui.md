# Task M5-T05: Alias-Tiers UI + Premium-Shortcode-Kauf

```yaml
phase: M5
workflow_state: ready
goal: "Creator zeigt Alias-Optionen abhängig vom Plan (Free 8+, Pro 4+, Business 2+). Premium-Shortcodes (≤4 Zeichen) sind kaufbar."
spec_refs: ["Pflichtenheft §M5.5 (8A)"]
risk: high
complexity: high
dependencies: ["EntitlementGate alias_min_length (fertig)", "Stripe-Integration"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Modify: `app/Livewire/QrCreator.php`, `app/Livewire/QrCodeEditor.php`, Creator-Blade
- Create: Premium-Shortcode-Prüfung, Stripe-Checkout-Flow für Kurz-Alias
- Out of scope: Komplettes Marketplace-Modell für Aliase

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Free-Nutzer | Alias-Feld min 8 Zeichen, Hinweis auf Pro |
| Pro-Nutzer | Alias-Feld min 4 Zeichen |
| Business-Nutzer | Alias-Feld min 2 Zeichen, Premium-Shortcode verfügbar |
| Premium-Kauf | Stripe-Checkout für ≤4 Zeichen Alias |

## Akzeptanz

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Creator validiert Alias-Mindestlänge nach Plan | Feature | Free: <8 rejected, Pro: <4 rejected |
| Premium-Shortcode bietet Kauf-Flow | Feature | Stripe-Checkout ausgelöst |
| Reserved-Paths (1 Zeichen, System) blockiert | Unit | QrCodeRouteService prüft |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `stripe`; Candidate Role: Backend Domain Developer; Tier: standard; Max Cost: medium.

```
Result: not started
```