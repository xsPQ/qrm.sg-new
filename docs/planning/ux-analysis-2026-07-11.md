# Kritische UX-Analyse — qrm.sg aus Kunden-/Nutzersicht

**Datum:** 11. Juli 2026
**Perspektive:** Erstnutzer, der ohne Vorwissen einen QR-Code erstellen will

---

## 1. Was sofort auffällt (kritisch)

### 1.1 Dashboard zeigt nur kryptische Codes, keine erkennbaren Namen
**Problem:** Die Tabelle zeigt `0DAqAi`, `r1Fvuv`, `BAKnA3` — das ist für den Nutzer wertlos. Das `title`-Feld existiert zwar (Test QR Code, Test Event…), aber es ist nicht prominent genug und wird nicht als primärer Anker genutzt. Ein Nutzer denkt in Namen wie „Geburtstagsparty" oder „Flyer Musterfirma", nicht in Kurzcodes.

**Auswirkung:** Nutzer verlieren den Überblick, sobald sie mehr als 3 QR-Codes haben.

### 1.2 Keine Analytics-Sichtbarkeit im Dashboard
**Problem:** Die Scans-Spalte zeigt nur eine Zahl. Klickt man drauf, passiert nichts. Es gibt keinen direkten Link zur Analytics-Seite (`/qr-codes/{id}/analytics`). Der Nutzer weiß nicht, dass Analytics existiert.

**Auswirkung:** Das wichtigste Verkaufsargument (Scans messen) ist unsichtbar.

### 1.3 Registrierungspflicht vor erstem QR-Code
**Problem:** Nutzer muss sich registrieren, bevor er überhaupt einen QR-Code erstellen kann. Das ist eine massive Conversion-Hürde. Konkurrenz (qr-code-generator.com, bitly) erlaubt anonyme Nutzung.

**Auswirkung:** Viel zu hohe Absprungrate bei Erstkontakt.

### 1.4 Keine visuelle QR-Anpassung
**Problem:** Der QR-Code ist schwarz-weiß, Standard-Pixel. Keine Farben, keine Logos, keine Dot-Patterns. Das ist 2026 nicht mehr zeitgemäß.

**Auswirkung:** qr.sg wirkt wie ein Basis-Tool, nicht wie ein Premium-Produkt.

### 1.5 Alias-Tiers nicht erkennbar
**Problem:** Custom Alias ist Pro-only, aber der Nutzer verstemt den Wert nicht. Es gibt keine Staffelung: Free = keine/long aliases, Pro = custom, Premium = kurze Premium-Codes.

**Auswirkung:** Monetarisierungspotenzial bleibt liegen.

---

## 2. Was fehlt komplett

### 2.1 QR-Code Naming / Anzeigename
Jeder QR-Code braucht einen **prominenten, nutzerdefinierten Namen**, der im Dashboard die Hauptspalte ist, nicht der Kurzcode. Der Name sollte:
- Im Dashboard als erste Spalte erscheinen
- In der URL-Leiste des Browsers beim Bearbeiten nutzbar sein
- Pflichtfeld sein (mit Default-Vorschlag)

### 2.2 Anonyme QR-Erstellung
Nutzer kann ohne Anmeldung 1-3 QR-Codes erstellen. Danach:
- Wasserzeichen / qrm.sg-Branding auf dem Code
- Hinweis: „Registriere dich, um Codes zu verwalten, zu ändern und Scans zu sehen"
- Codes sind 24h gültig, dann Löschung mit Hinweis
- Der bereits erstellte Code wird beim Registrieren ins Konto übernommen

### 2.3 Visuelle Anpassung (Pro/Business)
- **Farbe:** Vordergrund- und Hintergrundfarbe wählbar
- **Logo:** Upload eines Logos in die Mitte (mit Warnung zur Error-Correction)
- **Dot-Pattern:** Standard-Square, Rounded Dots, gestylte Pattern
- **Frame/Rand:** Optionaler Rahmen mit Call-to-Action (z.B. „Scan me")

### 2.4 A/B Testing
Ein QR-Code (gedruckt) leitet auf zwei verschiedene Ziel-URLs, je nach:
- Zufall (50/50)
- Zeit (A morgens, B abends)
- Gerät (Mobile → A, Desktop → B)
Metriken: Scans pro Variante, Conversion-Rate

### 2.5 Alias-Tier-System
| Tier | Was | Preis |
|---|---|---|
| Free | Zufälliger 6-Zeichen-Code | Kostenlos |
| Free | Custom Alias (8–32 Zeichen) | Kostenlos |
| Pro | Custom Alias (4–32 Zeichen) | Inklusive |
| Premium | Premium-Alias (1–4 Zeichen) | Einmalig oder monatlich, "Lifetime" = solange Dienst existiert |

---

## 3. Usability-Probleme (schnell behebbar)

| Problem | Impact | Aufwand |
|---|---|---|
| Analytics-Link fehlt im Dashboard | Hoch | Gering |
| Detail-Seite zeigt Code als Titel, nicht den Namen | Hoch | Gering |
| Creator: Titel-Feld ist optional und unscheinbar | Mittel | Gering |
| Kein QR-Code-Vorschau-Bild im Dashboard (nur Text) | Mittel | Mittel |
| Landing Page: kein „Try now" / QR direkt erstellen Button | Hoch | Mittel |
| `/creator` ist nicht intuitiv, besser `/create` oder „New QR" Button | Gering | Gering |

---

## 4. Aus Kundensicht: Was würde mich zum Bezahlen bringen?

1. **„Ich kann sehen, wie oft mein Code gescannt wurde"** → Analytics muss prominent sein
2. **„Mein QR-Code sieht professionell aus"** → Farben, Logo, Design
3. **„Ich kann den Link ändern ohne neu zu drucken"** → Das ist die Kernfunktion, muss klarer kommuniziert werden
4. **„Ich kann A/B Tests machen"** → Marketing-Killerfeature
5. **„Ich habe einen kurzen, einprägsamen Link"** → Premium-Alias

---

*Diese Analyse ist die Grundlage für die Feature-Definitionen und Aufgaben.*
