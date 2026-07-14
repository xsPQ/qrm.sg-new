# Feature Specs — qrm.sg v2

**Datum:** 11. Juli 2026
**Status:** Definiert, zur Umsetzung freigegeben

---

## FEAT-01: QR-Code Naming & Dashboard-Anker

**Priorität:** P0 — sofort umsetzen
**Aufwand:** Gering

### Was
- Dashboard-Tabelle: Spalte „Name" als erste Spalte (statt Code/Alias)
- Name = `title`-Feld, bereits vorhanden, wird aber nicht prominent gezeigt
- Detail-Seite: Name als `<h1>`, nicht der Kurzcode
- Creator: Name-Feld verpflichtend, mit sinnvollem Default-Vorschlag
- Code/Alias wird darunter als kleinere Zeile gezeigt

### Akzeptanz
- Dashboard zeigt Namen als primäre Spalte
- Klick auf Name → Detail-Seite
- Detail-Seite hat Namen als Titel
- Creator: Name ist Pflichtfeld

---

## FEAT-02: Analytics-Sichtbarkeit im Dashboard

**Priorität:** P0 — sofort umsetzen
**Aufwand:** Gering

### Was
- Dashboard-Tabelle: Scan-Zahl wird zum klickbaren Link → `/qr-codes/{id}/analytics`
- Icon/Button „Analytics" pro Zeile
- Mini-Sparkline (7-Tage) optional in der Zeile

### Akzeptanz
- Scan-Zahl ist klickbar und führt zur Analytics-Seite
- Analytics-Icon sichtbar in jeder Zeile

---

## FEAT-03: Anonyme QR-Erstellung

**Priorität:** P1
**Aufwand:** Mittel

### Was
- Landing Page: „Try Now" Button, leitet zu `/create` (ohne Login)
- Anonymer Nutzer kann 1 QR-Code erstellen (URL-Typ nur)
- QR-Code hat qrm.sg-Branding (kleines Wasserzeichen)
- Nach Erstellung: „Registriere dich, um diesen Code zu verwalten und Scans zu sehen"
- Code ist 24h gültig
- Bei Registrierung: Code wird ins Konto übernommen

### Akzeptanz
- Landing Page hat sichtbaren CTA
- `/create` funktioniert ohne Login
- Registrierungs-Hinweis nach Erstellung
- Code verschwindet nach 24h (Cleanup-Job)

---

## FEAT-04: Visuelle QR-Anpassung

**Priorität:** P1
**Aufwand:** Hoch

### Was
- **Farbe:** Foreground/Background Color Picker
- **Logo:** Upload (PNG/SVG), wird in die Mitte platziert
- **Dot-Pattern:** Square (default), Rounded, Dots
- **Frame:** Optionaler Rahmen mit CTA-Text
- **Plan-Restriktion:** Free = Schwarz/Weiß, Pro = Farben, Business = Logo + alle Patterns

### Akzeptanz
- Creator hat Design-Tab
- SVG-Download enthält die gewählte Anpassung
- PNG-Download enthält die gewählte Anpassung
- Free-Nutzer sieht Upgrade-Hinweis bei gesperrten Optionen

---

## FEAT-05: Alias-Tiers & Monetarisierung

**Priorität:** P2
**Aufwand:** Mittel

### Was
| Tier | Alias-Regeln | Preis |
|---|---|---|
| Free | Zufälliger 6-Zeichen-Code; Custom Alias 8–32 Zeichen | Kostenlos |
| Pro | Custom Alias 4–32 Zeichen | Im Abo inklusive |
| Premium | Premium-Alias 1–4 Zeichen | Einmalig oder monatlich |

Premium-Shortcodes (≤4 Zeichen) sind käuflich:
- 1 Zeichen: nicht verfügbar (reserviert)
- 2–3 Zeichen: teuer
- 4 Zeichen: mittel
- „Lifetime" = solange der Dienst existiert, kein rechtlicher Anspruch

### Akzeptanz
- Creator zeigt Alias-Optionen abhängig vom Tier
- Premium-Shortcodes haben Kauf-Flow
- Reservierte Systempfade bleiben geschützt

---

## FEAT-06: A/B Testing

**Priorität:** P2
**Aufwand:** Hoch

### Was
- Ein QR-Code kann mehrere Ziel-URLs haben (Varianten A, B, C)
- Verteilungsstrategie: Random 50/50, Gewichtbar, oder geräteabhängig
- Pro Variante werden Scans, Geräte, Zeit gemessen
- Ergebnisse im Analytics-Dashboard vergleichbar

### Akzeptanz
- Creator/Editor kann Varianten anlegen
- Resolver wählt Variante nach Strategie aus
- Analytics zeigt pro-Variante Metriken

---

## Priorisierung

| Sprint | Features | Status |
|---|---|---|
| **Sprint 1 (jetzt)** | FEAT-01, FEAT-02 | Sofort umsetzen |
| **Sprint 2** | FEAT-03 | Anonyme Erstellung |
| **Sprint 3** | FEAT-04 | Visuelle Anpassung |
| **Sprint 4** | FEAT-05 | Alias-Tiers |
| **Sprint 5** | FEAT-06 | A/B Testing |
