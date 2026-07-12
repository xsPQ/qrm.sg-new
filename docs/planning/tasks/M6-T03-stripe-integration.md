# PAYMENT-01: Zahlungsanbieter-Integration (Stripe)

## Status
🔨 Teilweise umgesetzt

## Priorität
Hoch

## Abhängigkeiten
- FEAT-08 (Fair-Use-Policy) für Upgrade-Prompts

## Beschreibung
Stripe-Integration via Laravel Cashier ist vorbereitet (composer.json, Controller, Migrations), aber nicht funktional aktiv (keine Stripe-Keys, ungetesteter Checkout-Flow).

## Bestand

| Komponente | Status | Hinweis |
|---|---|---|
| `laravel/cashier` in composer.json | ✅ | v16.6 |
| Billing-Controller | ✅ | Checkout, Portal, Webhook |
| Migrations (subscriptions, webhook_events) | ✅ | Vorhanden |
| Stripe-Keys (`.env`) | ❌ | Nicht konfiguriert |
| Stripe-Produkte/Preise (Dashboard) | ❌ | Müssen angelegt werden |
| Checkout-Flow getestet | ❌ | Nie durchgelaufen |
| Webhook-Handling getestet | ❌ | Stripe CLI lokal nötig |
| Premium-Short-URL-Monetarisierung | ❌ | Nur definiert, nicht umgesetzt |

## Aufgaben

### A1: Stripe konfigurieren
- Stripe-Konto erstellen (Test-Mode)
- Produkte anlegen: qrm.sg Pro (5€/Monat), qrm.sg Business (19€/Monat)
- Price-IDs in `.env` eintragen: `STRIPE_PRO_PRICE_ID`, `STRIPE_BUSINESS_PRICE_ID`
- `STRIPE_KEY` und `STRIPE_SECRET` konfigurieren
- `STRIPE_WEBHOOK_SECRET` konfiguriert

### A2: Checkout-Flow testen
- Upgrade-Button im Dashboard → Stripe Checkout
- Test-Karte: 4242 4242 4242 4242
- Webhook via `stripe listen --forward-to localhost/api/billing/webhook`
- Plan-Wechsel im DB bestätigen

### A3: Premium-Short-URLs monetarisieren
| Alias-Länge | Preis | Verfügbarkeit |
|---|---|---|
| 2–3 Zeichen | 9€/Monat (zusätzlich) | Business only |
| 4–7 Zeichen | Im Abo inklusive | Pro/Business |
| 8–32 Zeichen | Kostenlos | Alle |

Implementierung als Stripe-Addon oder separater Checkout.

### A4: Customer Portal testen
- Karten verwalten, Abo kündigen, Rechnungen herunterladen

## Verifikation
- End-to-End Checkout mit Test-Karte
- Webhook verarbeitet `checkout.session.completed` + `customer.subscription.updated`
- Plan-Wechsel in DB und UI sichtbar
- `php artisan test --filter=Billing`
