# FEAT-09: Branding & Eigenwerbung auf Resolver-Seiten

## Status
✅ Umgesetzt (Basis)

## Priorität
Mittel

## Abhängigkeiten
Keine

## Beschreibung
Die Resolver-Seiten (wo der Scanner landet) zeigen tier-abhängiges Branding:

| Tier | Branding |
|---|---|
| Free | "Powered by qrm.sg" Footer + dezenter "Get started" Hinweis |
| Pro | Sehr dezentes qrm.sg-Logo (nur Icon, kein Text) |
| Business | White-Label: kein Branding |

## Umgesetzt

- `resources/views/qr-types/layout.blade.php` prüft `qrCode.entitlementSnapshot()->plan()`
- Free: Footer mit Würfel-Icon + "Powered by **qrm.sg**" + "Create your own dynamic QR codes for free"
- Pro: Minimaler Footer (nur Icon, sehr klein)
- Business: Kein Footer
- i18n-Keys in DE/EN hinzugefügt
- 687 Tests grün

## Erweiterungspotential (später)

1. **Drittanbieter-Werbung bei Free:** Dezent Banner-Ad (z.B. Google AdSense) auf Free-Resolver-Seiten
2. **A/B-Testing des Brandings:** Verschiedene CTA-Texte testen
3. **Custom Footer für Business:** Optionaler eigener Footer-Text (White-Label konfigurierbar)
4. **Analytics auf Branding-Clicks:** Track wie viele Scanner auf "Get started" klicken

## Verifikation
- Resolver-Seite mit Free-QR → Footer sichtbar
- Resolver-Seite mit Pro-QR → Minimaler Footer
- Resolver-Seite mit Business-QR → Kein Footer
