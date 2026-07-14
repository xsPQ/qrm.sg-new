# FEAT-08: Fair-Use-Policy & AGB

## Status
❌ Nicht umgesetzt

## Priorität
Hoch (vor Produktivstart)

## Abhängigkeiten
Keine

## Beschreibung
Einführung von Fair-Use-Limits für Pro und Business, um Missbrauch zu verhindern. Erstellung der AGB als rechtliches Dokument.

## Fair-Use-Limits

| Limit | Free | Pro | Business |
|---|---|---|---|
| Aktive QR-Codes | 10 | 500 | 5.000 |
| Scans pro Tag (pro Code) | Unbegrenzt | 50.000 | 500.000 |
| API-Requests/Minute | — | 60 | 300 |
| Custom Aliases | 0 | Unbegrenzt (4–32 Zeichen) | Unbegrenzt (2–32 Zeichen) |
| A/B-Varianten pro Code | 0 | 5 | 20 |

Bei Überschreitung:
- Soft-Limit: Warn-E-Mail an Nutzer + Admin-Notification
- Hard-Limit (2x überschritten): Automatische Deaktivierung des Codes mit Upgrade-Prompt
- API: 429 Too Many Requests mit Retry-After Header

## Missbrauchsschutz

1. **Rate-Limiting Creator:** Max. 20 QR-Codes/Stunde pro User (Free: 5)
2. **Rate-Limiting Resolver:** Max. 100 Scans/Minute pro IP-Hash
3. **Suspicious Activity Detection:** Selbe IP scannt >500 Codes in 24h → Flag für Admin

## AGB

Die AGB müssen als statische Seite unter `/agb` (DE) und `/terms` (EN) bereitgestellt werden und mindestens enthalten:
- Leistungsbeschreibung
- Pflichten des Nutzers (keine illegalen Inhalte, keine Spam-QR-Codes)
- Fair-Use-Policy mit konkreten Limits
- Zahlungsbedingungen (Pro/Business)
- Kündigung und Bestandsschutz (§3.5.3)
- Haftungsausschluss
- Datenschutz-Verweis
- Gerichtsstand

## Verifikation
- `php artisan test --filter=FairUseLimitTest`
- AGB-Seite im Browser prüfen
- Rate-Limiter im Test überschreiten → korrekte Response
