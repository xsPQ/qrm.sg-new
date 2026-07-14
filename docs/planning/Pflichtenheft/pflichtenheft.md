# Pflichtenheft — qrm.sg

**Projektname:** QR-Message — qrm.sg
**Dokumentversion:** 1.4
**Datum:** 04.07.2026
**Status:** Zielarchitektur für Laravel-Release

---

## 1. Projekt-Pitch

**qrm.sg** ist ein **QR-Code-Content-Management-System mit Analytics und Monetarisierung** — ein Link-Shortener speziell für QR-Codes, der beliebige Inhaltstypen unterstützt.

Statt QR-Codes mit statischen Inhalten zu erzeugen, die sich nie wieder ändern lassen, erstellt qrm.sg **kurze, dynamische Links** (`qrm.sg/a7f3x2`), die hinter jedem QR-Code liegen. Der Ersteller kann den Inhalt jederzeit ändern, Scans tracken, Ablaufdaten setzen und Zugriffe begrenzen — alles über ein webbasiertes Dashboard.

**Das Problem:** Klassische QR-Codes sind starr. Einmal gedruckt, ist der Inhalt unveränderlich. Es gibt keine Statistiken, keine Kontrolle, keine Möglichkeit zur Aktualisierung.

**Die Lösung:** qrm.sg macht QR-Codes dynamisch, messbar und kontrollierbar — mit einem Freemium-Modell, das kostenlose Nutzung ermöglicht und Premium-Features für Power-User und Unternehmen anbietet.

**Kernversprechen:**
- QR-Codes in Sekunden erstellen — 8 Inhaltstypen
- Scans in Echtzeit tracken — Länder, Geräte, Zeitverläufe
- Inhalte ändern, ohne den QR-Code neu zu drucken
- Ablaufdaten, Nutzungslimits und Passwortschutz
- Kostenlos starten, bei Bedarf upgraden

### 1.1 Das zentrale Verkaufsargument: Editierbarkeit nach dem Druck

Jeder von qrm.sg erzeugte QR-Code kodiert **ausschließlich die Resolver-URL** (z. B. `https://qrm.sg/a7f3x2`). Die tatsächlichen Inhalte — WiFi-Zugangsdaten, Kontaktdaten, Event-Termine, Zahlungs-Links, Nachrichten — liegen **nicht im QR-Code selbst**, sondern werden zur Laufzeit vom Resolver aus der Datenbank geladen und auf einer qrm.sg-Seite gerendert oder als Weiterleitung ausgeliefert.

**Das bedeutet:** Der QR-Code bleibt unverändert gültig, auch wenn sich der Inhalt komplett ändert. Das physische Medium (Flyer, Plakat, Visitenkarte, Aufkleber) muss nicht neu gedruckt werden.

**Klassische Beispiele:**

| Szenario | Problem ohne qrm.sg | Lösung mit qrm.sg |
|---|---|---|
| **Event-Uhrzeit falsch** | Flyer bereits gedruckt, Uhrzeit vertippt → Teilnehmer kommen zur falschen Zeit | Uhrzeit im Dashboard ändern, QR-Code bleibt gleich, alle Scanner sehen sofort den korrekten Termin |
| **WiFi-Passwort geändert** | Aufkleber am Gäste-WLAN muss ausgetauscht werden | Neues Passwort im Dashboard eintragen, QR-Code auf dem Aufkleber bleibt gültig |
| **Kontakt-Daten aktualisiert** | Alte Visitenkarten mit falscher Nummer | Kontaktdaten im Dashboard editieren, QR-Code auf der Visitenkarte bleibt gleich |
| **Ziel-URL gewechselt** | Alte Werbeanzeige mit QR auf Landingpage A, Kampagne läuft aber auf Landingpage B | Ziel-URL im Dashboard ändern, gedruckter QR-Code bleibt aktiv |
| **Kampagne beendet** | QR-Code führt ins Leere | Status auf 'expired' setzen oder Ziel-URL austauschen, ohne den QR-Code austauschen zu müssen |

**Technische Garantie:** Die kanonische Nutzlastregel (§3.1.1) stellt sicher, dass jeder QR-Code von qrm.sg ausschließlich die Resolver-URL kodiert. Inhaltsänderungen sind jederzeit über das Dashboard möglich und treten sofort in Kraft. Der Resolver-Cache wird bei Inhaltsänderungen invalidiert.

**Umsetzungsentscheidung ab 03.06.2026:** Die bestehende Deno/TypeScript-Fassung dient nur noch als fachliche Referenz für Verhalten, Datenmodell und bestehende Erkenntnisse. Die produktive Zielimplementierung wird als Laravel-Neuentwicklung spezifiziert.

**Verbindlicher Gesamtumfang ab 04.07.2026:** Das Projekt wird vollständig über die Meilensteine M0 bis M4 umgesetzt. Die Meilensteine steuern Reihenfolge, Abnahme und risikoarme Auslieferung; sie kennzeichnen keine optionalen oder dauerhaft entfallenden Produktteile. Eine frühe nutzbare Version darf inkrementell bereitgestellt werden, das Projektziel bleibt jedoch der vollständige in diesem Pflichtenheft beschriebene Funktionsumfang einschließlich aller acht QR-Typen, Monetarisierung, Analytics, Administration und Business-Erweiterungen.

---

## 2. Zusammenfassung der Tarif-Optionen

### 2.1 Free-Tier

| Feature | Umfang |
|---|---|
| Aktive QR-Codes | 10 Stück |
| Gültigkeit | Immer exakt 30 Tage ab Erstellung; danach automatischer Ablauf |
| Analytics | Scan-Anzahl + letzter Scan |
| QR-Typen | Alle 8 Typen |
| Branding | qrm.sg-Logo unter dem QR-Code |
| Download | SVG + PNG |
| Custom Alias | Nein |
| Passwortschutz | Nein |

**Zielgruppe:** Gelegenheitsnutzer, die QR-Codes für private Zwecke erstellen.

### 2.2 Pro — 5 EUR/Monat

|| Feature | Umfang |
|---|---|
| Aktive QR-Codes | Unbegrenzt |
| Gültigkeit | Unbegrenzt (kein automatischer Ablauf) |
| Analytics | Vollständig: Länder, Geräte, Zeitverläufe, Referer |
| QR-Typen | Alle 8 Typen |
| Branding | Ohne qrm.sg-Logo |
| Download | SVG + PNG in hoher Auflösung |
| Custom Alias | Ja — `qrm.sg/mein-link` |
| Passwortschutz | Ja |
| API-Zugang | Optionales Add-on: +1 EUR/Monat für REST-API mit Token-Auth |
| Support | E-Mail-Support |

**Zielgruppe:** Freiberufler, Kleinunternehmen, Agenturen.

### 2.3 Business — 19 EUR/Monat

|| Feature | Umfang |
|---|---|
| Alles aus Pro | Ja |
| API-Zugang | REST API mit Rate-Limits (inklusive, kein Aufpreis) |
| Eigene Domain | `qr.firma.de` — Custom Domain Setup |
| Team-Verwaltung | Mehrere Nutzer mit Rollen |
| White-Label | Eigene Branding-Optionen |
| Bulk-Import/Export | CSV-Import für Kampagnen |
| Prioritäts-Support | Antwort innerhalb 24h |

**Zielgruppe:** Mittelständische Unternehmen, Agenturen, Event-Veranstalter.

### 2.4 Conversion-Hebel

| Mechanismus | Beschreibung |
|---|---|
| QR-Limit | Nach 10 QR-Codes → Upgrade-Prompt |
| Ablauf-Prompt | 3 Tage vor Ablauf → E-Mail mit Pro-Verweis |
| Analytics-Vorschau | Free zeigt nur Gesamtzahl — Pro zeigt Aufschlüsselung |
| Branding | Kleines qrm.sg-Logo unter jedem Free-QR-Code |

**Verbindliche Free-Tier-Regel:** Das Backend setzt bei jeder mit Free-Berechtigung erstellten QR-Ressource `expires_at = created_at + 30 Tage`. Free-Nutzer können dieses Datum weder entfernen noch vor- oder zurücksetzen. Für mit Pro- oder Business-Berechtigung erstellte QR-Ressourcen bleibt `null` als unbegrenzte Gültigkeit oder ein frei gewähltes zukünftiges Ablaufdatum zulässig. Als aktiv zählt ein QR-Code nur, wenn `is_active = true`, er nicht gelöscht ist, sein Ablaufzeitpunkt noch nicht erreicht wurde und weder Burn noch `max_scans` ihn verbraucht haben. Nur aktive QR-Codes mit Free-Berechtigung zählen gegen das Free-Limit von zehn; grandfathered Pro-/Business-Ressourcen werden nach einem Downgrade nicht mitgezählt.

---

## 3. Funktionsbeschreibung

### 3.1 QR-Code-Erstellung (Creator)

Die zentrale Funktion der Plattform. Über ein webbasiertes Formular erstellt der Nutzer QR-Codes mit einem der 8 unterstützten Typen.

#### 3.1.1 Unterstützte QR-Code-Typen

| Typ | Inhalt | QR-Datenformat | Beschreibung |
|---|---|---|---|
| **Message** | Freitext-Nachricht | `https://qrm.sg/:code` | Zeigt die hinterlegte Nachricht auf einer qrm.sg-Seite an |
| **URL** | Weiterleitung zu URL | `https://qrm.sg/:code` | Leitet den Scanner zur hinterlegten Ziel-URL weiter |
| **WiFi** | WLAN-Zugangsdaten | `https://qrm.sg/:code` | Zeigt WLAN-Daten und eine nutzbare WiFi-Hilfe auf einer qrm.sg-Seite an |
| **Crypto** | Krypto-Zahlung | `https://qrm.sg/:code` | Zeigt Zahlungsdaten und einen Wallet-Link an |
| **Social** | Social-Media-Profil | `https://qrm.sg/:code` | Leitet zum hinterlegten Social-Media-Profil weiter |
| **Redirect** | HTTP-Weiterleitung | `https://qrm.sg/:code` | Antwortet mit dem konfigurierten HTTP-Redirect 301 oder 302 |
| **Event** | Kalendereintrag | `https://qrm.sg/:code` | Zeigt Termindaten und bietet einen ICS-Download an |
| **Contact** | Kontaktinformationen | `https://qrm.sg/:code` | Zeigt Kontaktdaten und bietet einen vCard-Download an |

**Kanonische Nutzlastregel:** Jeder von qrm.sg erzeugte und heruntergeladene QR-Code kodiert ausschließlich die unveränderliche Resolver-URL mit dem systemgenerierten Code, beispielsweise `https://qrm.sg/a7f3x2`. Die fachlichen Inhalte wie Text, WiFi-Daten, Wallet-URI, ICS oder vCard werden nicht direkt in den äußeren QR-Code geschrieben, sondern erst nach dem Aufruf der URL durch den Resolver ausgeliefert. Dadurch bleiben Inhalt, Ziel und Darstellung nach dem Druck änderbar und jeder Aufruf kann nach den Analytics-Regeln erfasst werden. Ein Custom Alias ist ein zusätzlicher öffentlicher Einstiegspunkt, aber nicht die kanonische Nutzlast der Download-Artefakte.

#### 3.1.2 Erstellungsparameter

| Parameter | Typ | Pflicht | Standard | Beschreibung |
|---|---|---|---|---|
| `type` | String | Ja | — | Einer der 8 Typen |
| `content` | JSONB | Ja | — | Typ-spezifische Daten |
| `alias` | String | Nein | `null` | Custom Alias für Pro/Business, nach Normalisierung 3–32 Zeichen, global eindeutig im gemeinsamen Pfadnamensraum und kein reservierter Systempfad |
| `password` | String | Nein | `null` | Passwortschutz (Pro/Business) |
| `expires_at` | ISO 8601 | Nein | Planabhängig | Free: wird serverseitig unveränderlich auf exakt 30 Tage ab Erstellung gesetzt; Pro/Business: zwei Modi — `null` = unbegrenzt (bis Service-Ende) oder frei wählbares zukünftiges Ablaufdatum |
| `burn` | Boolean | Nein | `false` | Einmal-Nutzung: genau eine Resolver-Auslieferung, danach deaktiviert |
| `max_scans` | Integer | Nein | `null` | Maximale Scans, `null` = unbegrenzt |

**Alias-Normalisierung:** Aliase werden vor der Prüfung getrimmt und in ASCII-Kleinbuchstaben überführt. Zulässig sind 3–32 Zeichen nach dem Muster `^[a-z0-9]+(?:-[a-z0-9]+)*$`. Systempfade werden zentral konfiguriert und mindestens `api`, `login`, `register`, `dashboard`, `qr-codes`, `billing`, `admin`, `nova`, `logout`, `password`, `verify`, `health`, `storage`, `build` und `.well-known` reserviert. Alias und systemgenerierter Code teilen sich denselben, case-insensitiven Namensraum gemäß Abschnitt 6.2.

#### 3.1.3 Typ-spezifische Content-Felder

**Message:**
| Feld | Typ | Pflicht | Max. Länge |
|---|---|---|---|
| `text` | String | Ja | 4096 Zeichen |

**URL:**
| Feld | Typ | Pflicht | Validierung |
|---|---|---|---|
| `url` | String | Ja | Muss mit `http://` oder `https://` beginnen |

**WiFi:**
| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `ssid` | String | Ja | Netzwerkname |
| `encryption` | String | Ja | Verschlüsselungstyp (WPA, WEP, nopass) |
| `password` | String | Nein | Netzwerkpasswort |
| `hidden` | Boolean | Nein | Verstecktes Netzwerk |

**Crypto:**
| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `coin` | String | Ja | Währung: bitcoin, ethereum, litecoin |
| `address` | String | Ja | Wallet-Adresse |
| `amount` | Number | Nein | Betrag |

**Social:**
| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `platform` | String | Ja | Plattform: twitter, instagram, facebook |
| `handle` | String | Ja | Profil-URL oder Handle |

**Redirect:**
| Feld | Typ | Pflicht | Validierung |
|---|---|---|---|
| `url` | String | Ja | Muss mit `http://` oder `https://` beginnen |
| `status` | String | Nein | `301` oder `302`, Standard: `302` |

**Event:**
| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `title` | String | Ja | Termin-Titel |
| `start` | ISO 8601 | Ja | Beginn |
| `end` | ISO 8601 | Ja | Ende |
| `location` | String | Nein | Ort |
| `description` | String | Nein | Beschreibung |

**Contact:**
| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `firstName` | String | Bedingt | Vorname (firstName oder lastName erforderlich) |
| `lastName` | String | Bedingt | Nachname |
| `org` | String | Nein | Organisation |
| `title` | String | Nein | Position/Titel |
| `phone` | String | Nein | Telefonnummer |
| `email` | String | Nein | E-Mail-Adresse |
| `url` | String | Nein | Webseite |
| `street` | String | Nein | Straße |
| `city` | String | Nein | Stadt |
| `region` | String | Nein | Bundesland/Region |
| `postal` | String | Nein | Postleitzahl |
| `country` | String | Nein | Land |
| `nickname` | String | Nein | Spitzname |
| `bday` | String | Nein | Geburtstag |
| `note` | String | Nein | Notiz |
| `version` | String | Nein | vCard-Version (2.1, 3.0, 4.0), Standard: 4.0 |

#### 3.1.4 Ablauf der Erstellung

1. Nutzer wählt QR-Typ in der Sidebar
2. Dynamisches Formular wird angezeigt (typ-spezifische Felder)
3. Nutzer füllt Pflichtfelder aus und setzt optionale Parameter
4. Frontend sendet `POST /api/qr` mit validiertem JSON-Body an die Laravel-Anwendung
5. Backend validiert alle Eingaben serverseitig über dedizierte Form Requests
6. Backend generiert einen eindeutigen öffentlichen Code (mindestens 6 Zeichen, kryptografisch sicher) und reserviert ihn transaktional im gemeinsamen Pfadnamensraum
7. Backend setzt die planabhängigen Regeln, insbesondere den verpflichtenden 30-Tage-Ablauf im Free-Tier, und speichert Daten und öffentliche Route in PostgreSQL
8. Backend gibt interne ID, öffentlichen Code, Resolve-URL und Metadaten zurück
9. Backend erzeugt die kanonischen Download-Artefakte (SVG/PNG) mit der unveränderlichen Code-URL serverseitig oder stellt sie on demand bereit; die UI darf zusätzlich eine Vorschau rendern
10. Nutzer kann QR-Code als SVG oder PNG herunterladen

---

### 3.2 QR-Code-Auflösung (Resolver)

Der Resolver ist der **Hot Path** der Anwendung — er wird bei jedem Scan eines QR-Codes aufgerufen.

#### 3.2.1 Ablauf der Auflösung

```
Scanner → GET qrm.sg/:codeOderAlias → globaler Pfadnamensraum → Redis/PostgreSQL → Zugriffsfreigabe → Typ-basierte Antwort
                                                                                                  → Scan-Tracking per Queue
```

1. Scanner öffnet `qrm.sg/:code` oder alternativ `qrm.sg/:alias`.
2. Der Resolver normalisiert den Pfad case-insensitiv und löst ihn über den gemeinsamen, global eindeutigen Pfadnamensraum aus `qr_code_routes` auf.
3. Unbeschränkte QR-Codes dürfen zuerst aus Redis gelesen werden; bei Cache-Miss erfolgt der Lookup in PostgreSQL und der Cache wird aktualisiert.
4. Inaktive, abgelaufene oder bereits vollständig verbrauchte QR-Codes liefern eine 410-Gone-Seite. Unbekannte Pfade liefern 404.
5. Für QR-Codes mit `burn = true` oder gesetztem `max_scans` reserviert der Resolver **vor der Auslieferung** atomar genau einen Zugriff in PostgreSQL. Ein bedingtes Update erhöht `scan_count` nur, wenn der Datensatz aktiv, nicht abgelaufen und noch nicht ausgeschöpft ist.
6. Der atomar zugelassene Zugriff wird ausgeliefert. Bei `burn = true` wird der QR-Code dabei deaktiviert, aber nicht physisch gelöscht. Bei `max_scans = N` werden exakt N zugelassene Antworten ausgeliefert; der nächste und alle späteren Zugriffe erhalten 410.
7. Parallele Zugriffe dürfen das Limit nicht überschreiten. Bei einem Burn-QR-Code gewinnt genau eine Anfrage; alle konkurrierenden oder späteren Anfragen erhalten 410. Cache-Einträge werden bei Deaktivierung, Ablauf, Limit-Erreichung und Inhaltsänderung invalidiert.
8. Jeder zugelassene Resolver-Aufruf zählt, sobald die atomare Zugriffsfreigabe erfolgt ist, auch wenn der Client die anschließend erzeugte Antwort nicht vollständig abruft. Das asynchrone Scan-Event enthält denselben Zugriffszeitpunkt und darf keinen zweiten Zähleranstieg verursachen.
9. Für unbeschränkte QR-Codes wird das Scan-Event asynchron per Queue erfasst; für beschränkte QR-Codes wird die synchrone Limit-Reservierung durch den asynchronen Detaildatensatz ergänzt.
10. Der Resolver liefert die typbasierte Antwort aus.

#### 3.2.2 Typ-basierte Antworten

| Typ | Antwort | HTTP-Status |
|---|---|---|
| `url` | HTTP-Redirect zur Ziel-URL | 302 |
| `redirect` | HTTP-Redirect mit konfigurierbarem Status | 301 oder 302 |
| `social` | HTTP-Redirect zur Profil-URL | 302 |
| `message` | HTML-Seite mit Nachricht | 200 |
| `wifi` | HTML-Seite mit WLAN-Daten | 200 |
| `crypto` | HTML-Seite mit Zahlungsdaten + Wallet-Link | 200 |
| `event` | HTML-Seite mit Termin + ICS-Download-Link | 200 |
| `contact` | HTML-Seite mit Kontaktdaten + vCard-Download | 200 |

#### 3.2.3 Leistungsanforderung

| Szenario | Zielzeit |
|---|---|
| Resolve mit warmem Redis-Cache | p95 < 50 ms App-Zeit |
| Resolve mit Cache-Miss | p95 < 150 ms App-Zeit |
| Scan-Tracking | Asynchron per Queue, blockiert nicht die Antwort |

---

### 3.3 Scan-Tracking und Analytics

#### 3.3.1 Erfasste Daten pro Scan

| Datentyp | Feld | Quelle | Speicherung |
|---|---|---|---|
| Zeitstempel | `scanned_at` | Serverzeit | Direkt |
| Öffentlicher Einstieg | `request_host`, `route_kind` | Resolver-Route | Host sowie Aufruf über Systemcode oder Alias |
| IP-Adresse (pseudonymisiert) | `ip_hash` | Verifizierte Client-IP aus Laravel Trusted Proxies | HMAC-SHA-256 mit serverseitigem Secret, keine Klartext-IPs |
| Vollständiger User-Agent | `user_agent` | `User-Agent`-Header | Längenbegrenzt als Rohwert |
| Gerätetyp | `device_type` | User-Agent | `mobile`, `desktop`, `tablet` |
| Betriebssystem | `os` | User-Agent | `Windows`, `macOS`, `Android`, `iOS`, `Linux` |
| Browser | `browser` | User-Agent | `Chrome`, `Firefox`, `Safari`, `Edge` |
| Bot-Erkennung | `is_bot` | User-Agent-Auswertung | Boolean |
| Referer | `referer` | `Referer`-Header | Vollständige, längenbegrenzte URL |
| Sprache | `accept_language` | `Accept-Language`-Header | Längenbegrenzt |
| Kampagnenparameter | `utm` | Query-Parameter | Nur `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` als JSONB |
| Geodaten | `country`, `region`, `city`, `asn` | Lokale GeoIP-Datenbank | Keine Übermittlung der Client-IP an einen externen GeoIP-Dienst |
| Resolver-Ergebnis | `response_type`, `http_status` | Anwendung | Typ der Antwort und HTTP-Status |
| Antwortzeit | `response_time_ms` | Anwendung | Serverseitig gemessene Millisekunden |

#### 3.3.2 Aggregierte Statistiken

| Tabelle | Aggregation | Zweck |
|---|---|---|
| `scan_stats_hourly` | Scans pro Stunde pro QR-Code | Echtzeit-Diagramme |
| `scan_stats_daily` | Scans pro Tag pro QR-Code | Tagesübersicht, eindeutige IPs |

#### 3.3.3 Analytics-Endpunkte

| Endpunkt | Beschreibung | Auth |
|---|---|---|
| `GET /api/qr/{code}/stats` | Gesamtstatistiken + letzte 20 Scans | Session, Besitzer oder Admin |
| `GET /api/qr/{code}/stats/daily` | Tägliche Aggregationen | Session, Besitzer oder Admin |
| `GET /api/qr/{code}/stats/hourly` | Stündliche Aggregationen | Session, Besitzer oder Admin |

#### 3.3.4 Datenschutz-Konformität

- IPs werden **nicht im Klartext** gespeichert; das Pseudonym entsteht über HMAC-SHA-256 mit einem getrennt verwalteten serverseitigen Secret
- Kein Browser-Fingerprinting
- Die vollständige freigegebene Analytics-Allowlist aus Abschnitt 3.3.1 wird erfasst; beliebige zusätzliche Header werden nicht gespeichert
- GeoIP erfolgt über eine lokal betriebene Datenbank, damit die Client-IP nicht an einen externen Dienst übertragen wird
- Rohscans von QR-Codes mit Free-Berechtigung werden 60 Tage nach Erstellung des QR-Codes gelöscht; Rohscans mit Pro-/Business-Berechtigung werden jeweils 24 Monate nach `scanned_at` gelöscht
- Aggregierte Statistiken bleiben bestehen, solange der zugehörige QR-Code und Account bestehen; bei QR-Code- oder Account-Löschung greifen die definierten Cascade-Löschungen
- Klartext-IP, Cookies, Authorization-Header, Passwörter und Browser-Fingerprints werden nie gespeichert
- Die rechtliche Prüfung, Datenschutzerklärung und Dokumentation der Verarbeitung sind ein verpflichtendes Gate vor öffentlichem Produktivstart, dürfen aber parallel zur technischen Entwicklung abgeschlossen werden

---

### 3.4 Nutzerverwaltung und Authentifizierung (ab M2)

#### 3.4.1 Web-Authentifizierung

| Funktion | Beschreibung |
|---|---|
| Registrierung | E-Mail + Passwort über das offizielle Laravel Starter Kit (Livewire-Variante) |
| Login | Session-basierter Login |
| Auth-Methode Web | Session-Cookie + CSRF-Schutz |
| E-Mail-Verifikation | Pflicht vor Freischaltung sensibler Funktionen und vor kostenpflichtigem Upgrade |
| Passwort-Reset | E-Mail-basiert |

#### 3.4.2 API-Authentifizierung

| Funktion | Beschreibung |
|---|---|
| API-Zugriff für Business | Laravel Sanctum Personal Access Tokens |
| Token-Scopes | Mindestens `qr:read`, `qr:write`, `stats:read`, `billing:read` |
| Token-Verwaltung | Erstellen, Anzeigen, Widerrufen im Dashboard |

#### 3.4.3 Nutzerdaten und Accountstatus

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | BIGINT | Interne Nutzer-ID |
| `name` | TEXT | Anzeigename |
| `email` | TEXT | Eindeutige E-Mail-Adresse |
| `email_verified_at` | TIMESTAMPTZ | Verifikationszeitpunkt |
| `password_hash` | TEXT | Argon2id-Hash |
| `status` | TEXT | `active`, `suspended`, `deleted` |
| `plan` | TEXT | Tarif: `free`, `pro`, `business` |
| `stripe_id` | TEXT | Stripe-Kunden-ID |
| `created_at` | TIMESTAMPTZ | Registrierungsdatum |
| `updated_at` | TIMESTAMPTZ | Letzte Aktualisierung |

#### 3.4.4 Rollenmodell

| Rolle | Bereich | Rechte |
|---|---|---|
| `user` | Standardkunde | Eigene QR-Codes und eigene Abrechnung verwalten |
| `admin` | Interner Betrieb | Nutzer, QR-Codes, Pläne und Betriebsfunktionen verwalten |
| `owner`, `manager`, `member` | Business-Team (ab M4) | Rollenbasiertes Team-Modell für Mehrnutzer-Konten |

---

### 3.5 Bezahl-System (ab M2)

#### 3.5.1 Stripe-Integration

| Komponente | Beschreibung |
|---|---|
| Payment Provider | Stripe |
| Laravel-Integration | Laravel Cashier |
| Checkout | Stripe Checkout |
| Customer Portal | Stripe Customer Portal |
| Webhook | `POST /api/billing/webhook` mit Signaturprüfung |
| Subscription-Management | Upgrade, Downgrade, Kündigung und Status-Synchronisierung über Cashier + Webhooks |

**Quelle der Wahrheit für bezahlte Features ist ausschließlich der erfolgreich validierte Stripe-Webhook.** Ein Browser-Redirect nach Checkout reicht nicht als Freischaltungsnachweis.

#### 3.5.2 Zahlungsfluss

1. Nutzer klickt im Dashboard auf "Upgrade"
2. Backend erstellt eine Stripe-Checkout-Session
3. Redirect zu Stripe Checkout
4. Nach erfolgreicher Zahlung: Redirect zurück zu qrm.sg
5. Stripe Webhook bestätigt Zahlung serverseitig
6. Backend aktualisiert Tarif- und Subscription-Daten idempotent in der Datenbank
7. Dashboard zeigt neue Features erst nach bestätigtem Webhook als freigeschaltet

#### 3.5.3 Tarif-Downgrade und Bestandsschutz

Jeder QR-Code erhält bei Erstellung einen unveränderlichen Berechtigungs-Snapshot des zu diesem Zeitpunkt wirksamen Tarifs. Ein späteres Downgrade oder eine Kündigung ändert diesen Snapshot nicht. Damit bleiben bereits während eines bezahlten Abos angelegte QR-Codes zu ihren damaligen Konditionen funktionsfähig.

Für eine grandfathered Pro-/Business-Ressource bleiben insbesondere Gültigkeit, Ablaufdatum, Custom Alias, Passwortschutz, Branding/White-Label, Downloadqualität, Analytics-Umfang und bereits zugeordnete Custom-Domain-Routen erhalten. Die Ressource darf weiterhin angezeigt, inhaltlich bearbeitet, heruntergeladen und ausgewertet werden. Ein Downgrade setzt kein neues Ablaufdatum und deaktiviert keine bestehende Route.

Nach Ende des Abos gelten für **neu angelegte** QR-Codes und neu hinzukommende Ressourcen die Rechte des aktuellen Tarifs. Wiederkehrende Kontoleistungen sind nicht Bestandteil des QR-Ressourcen-Snapshots: Neue API-Tokens, neue Custom Domains, zusätzliche Teammitglieder, neue White-Label-Konfigurationen, Bulk-Import/-Export und Prioritäts-Support stehen nach Ende des entsprechenden Abos nicht mehr zur Verfügung. Bereits an grandfathered QR-Codes gebundene Aliase, Passwörter, Branding-Einstellungen und Custom-Domain-Routen bleiben bestehen.

Der Stripe-Webhook aktualisiert somit den aktuellen Account-Tarif, darf aber niemals die gespeicherten Berechtigungs-Snapshots vorhandener QR-Codes überschreiben. Upgrade, Downgrade und Webhook-Wiederholung müssen idempotent getestet werden.

---

### 3.6 Dashboard und Administration (ab M2)

#### 3.6.1 Dashboard-Funktionen

| Funktion | Beschreibung |
|---|---|
| QR-Code-Liste | Übersicht aller eigenen QR-Codes mit Status, Plan-Hinweisen und letzter Aktivität |
| QR-Code-Detail | Einzelansicht mit Inhalt, Einstellungen, Download und Statistiken |
| Bearbeiten | Inhalt, Einstellungen und QR-Typ ändern; Fehlerkorrektur änderbar mit Bestätigungshinweis (neues QR-Bild) |

**QR-Typ-Wechsel im Edit-Modus:** Da jeder QR-Code ausschließlich die Resolver-URL kodert (Kanonische Nutzlastregel, §3.1.1), ist der QR-Typ nachträglich änderbar. Ein Wechsel des Typs ändert nur den codierten Inhalt (was hinter der URL liegt), nicht aber das gedruckte QR-Bild. Die typspezifischen Formularfelder werden beim Wechsel neu geladen; der bisherige Inhalt wird durch typspezifische Standardwerte ersetzt.
| Löschen | QR-Code entfernen |
| Analytics | Scan-Statistiken je QR-Code |
| Account | Profil, Passwort, E-Mail-Verifikation, Tarif, Abrechnung |
| API-Tokens | Verwaltung der Business-API-Tokens ab M4 |

#### 3.6.2 Admin-Panel

| Funktion | Beschreibung |
|---|---|
| Werkzeug | Laravel Nova |
| Zugriff | Nur interne Admin-Rolle |
| Nutzerverwaltung | Suchen, Sperren, Entsperren, Plan ändern, E-Mail-Status einsehen |
| QR-Verwaltung | QR-Codes einsehen, deaktivieren, Support-Fälle nachvollziehen |
| Billing-Einblick | Stripe-Kunden-ID, Subscription-Status, Zahlungsereignisse |
| Betriebsfunktionen | Queue-, Job- und Fehlersicht über angebundene Betriebswerkzeuge |

---

### 3.7 QR-Code-Download

| Format | Beschreibung |
|---|---|
| SVG | Vektorgrafik, skalierbar, druckfähig |
| PNG | Rastergrafik, hohe Auflösung (Pro/Business) |

Die kanonischen QR-Code-Dateien werden **serverseitig** von der Laravel-Anwendung erzeugt. Eine clientseitige Vorschau im Dashboard ist erlaubt, aber nicht die Quelle der Wahrheit für Downloads, Druckdateien oder Branding-Regeln.

#### 3.7.1 Verbindliches Download-Verhalten

| Endpunkt | Content-Type | Verhalten |
|---|---|---|
| `GET /api/qr/{code}/download.svg` | `image/svg+xml` | Liefert die kanonische, skalierbare Druckdatei |
| `GET /api/qr/{code}/download.png` | `image/png` | Liefert die kanonische PNG-Datei in der für den Tarif erlaubten Auflösung |

- Beide Endpunkte erfordern eine Session und Besitzer- oder Admin-Rechte.
- `{code}` ist immer der unveränderliche systemgenerierte Code, nicht ein Alias.
- Das kodierte Ziel ist immer `https://qrm.sg/{code}` beziehungsweise bei einer freigeschalteten Custom Domain deren kanonische Resolver-URL.
- Free-Downloads enthalten das definierte qrm.sg-Branding; Pro- und Business-Downloads werden ohne qrm.sg-Branding erzeugt. Die serverseitige Tarifprüfung ist maßgeblich.
- Die Antwort setzt einen sicheren Dateinamen wie `qrm-a7f3x2.svg` oder `qrm-a7f3x2.png` und verhindert Content-Disposition-/Header-Injection.
- Eine Inhaltsänderung erzeugt keinen neuen QR-Code und keine neue öffentliche Code-URL. Eine Änderung an Domain- oder Branding-Einstellungen invalidiert vorhandene Download-Artefakte.

---

## 4. Technische Architektur

### 4.1 Technologie-Stack

| Komponente | Technologie | Begründung |
|---|---|---|
| **Backend + Web-App** | PHP 8.3 + Laravel 13 | Schnellstes belastbares Release-Setup mit großem Ökosystem |
| **Web-Authentifizierung** | Offizielles Laravel Starter Kit (Livewire) | Standardisiertes Login-, Reset- und Verify-Skelett |
| **API-Authentifizierung** | Laravel Sanctum | Native Token-Lösung für spätere Business-API |
| **Admin-Panel** | Laravel Nova | Fertige interne Verwaltung für Release-Betrieb |
| **Billing** | Stripe + Laravel Cashier | Ausgereiftes Subscription- und Webhook-Modell |
| **Rollen/Rechte** | `spatie/laravel-permission` | Bewährtes Rollen- und Berechtigungssystem |
| **Datenbank** | PostgreSQL | Bewährt, relationale Stärke, JSONB, gute Indexierung |
| **Cache + Queue** | Redis | Resolver-Cache, Sessions, Rate-Limits und Jobs |
| **Queue-Betrieb** | Laravel Queue + Horizon | Asynchrone Jobs und Beobachtbarkeit |
| **Frontend** | Blade + Livewire + Alpine.js + Tailwind CSS | Schnelle Produktentwicklung ohne SPA-Overhead |
| **Asset-Build** | Vite | Standard-Build-Pipeline für Laravel |
| **QR-Erzeugung** | Selbstgehostete QR-Library, serverseitig eingebunden | Konsistente Downloads und volle Kontrolle |
| **Auslieferung** | Docker-Images + Docker Compose | Reproduzierbares erstes Deployment unabhängig vom späteren Host |
| **Produktionsziel** | Docker-fähiger VPS oder vergleichbare Cloud, noch festzulegen | Anbieter und Region bleiben eine Betriebsentscheidung |

#### 4.1.1 Erste Docker-Bereitstellung

Die erste vollständig lauffähige Bereitstellung wird als versionierter Docker-Compose-Stack geliefert. Ein einzelner Container reicht wegen Webserver, Queue, Scheduler, PostgreSQL und Redis nicht aus; alle Dienste werden jedoch gemeinsam deklarativ gestartet und betrieben.

| Service | Aufgabe |
|---|---|
| `nginx` | Öffentlicher HTTP-/HTTPS-Einstieg und Weiterleitung an Laravel |
| `app` | Laravel/PHP-FPM für Web-, API- und Resolver-Anfragen |
| `worker` | Dasselbe Anwendungs-Image mit Queue-/Horizon-Prozess |
| `scheduler` | Dasselbe Anwendungs-Image für Laravel Scheduler |
| `postgres` | Persistente Produktdatenbank |
| `redis` | Cache, Sessions, Rate-Limits und Queue |

Verbindlich sind reproduzierbare Image-Builds, Healthchecks, persistente Volumes, eine `.env.example` ohne Secrets, Secret-Zufuhr ausschließlich zur Laufzeit, ein expliziter Migrationsschritt sowie dokumentierte Start-, Stop-, Update-, Backup- und Restore-Befehle. Das Anwendungs-Image wird nicht für jeden Prozess separat gebaut, sondern mit unterschiedlichen Startkommandos wiederverwendet. Ein späterer Wechsel des Hosts oder die Ergänzung eines CDN darf keine Änderung der Produktlogik erfordern.

### 4.2 Architekturdiagramm

```
┌───────────────────────────────────────────────────────────┐
│                         Internet                          │
│                                                           │
│  Scanner       Nutzer-Dashboard       Admin-Panel         │
└───────────────┬───────────────────────┬───────────────────┘
                │                       │
                ▼                       ▼
         ┌─────────────────────────────────────┐
         │            Nginx / HTTPS            │
         └─────────────────┬───────────────────┘
                           ▼
         ┌─────────────────────────────────────┐
         │           Laravel Anwendung         │
         │  Web Routes / API / Resolver / Nova │
         └──────────────┬──────────────┬───────┘
                        │              │
                        ▼              ▼
               ┌──────────────┐   ┌──────────────┐
               │    Redis     │   │ PostgreSQL   │
               │ Cache/Queue  │   │ Produktdaten │
               └──────┬───────┘   └──────┬───────┘
                      │                  │
                      ▼                  ▼
               ┌──────────────┐   ┌──────────────┐
               │ Horizon/Jobs │   │ Aggregation  │
               └──────┬───────┘   └──────────────┘
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
     Stripe      Mail-Provider   Logging/Monitoring
```

### 4.3 Projektstruktur

```
app/
├── Actions/
│   ├── Analytics/
│   ├── Billing/
│   ├── Qr/
│   └── Users/
├── Domain/
│   ├── Analytics/
│   ├── Billing/
│   ├── Qr/
│   └── Users/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   ├── Billing/
│   │   └── Web/
│   ├── Middleware/
│   └── Requests/
├── Jobs/
├── Livewire/
├── Models/
├── Nova/
├── Notifications/
├── Policies/
└── Support/
bootstrap/
config/
database/
├── factories/
├── migrations/
└── seeders/
public/
resources/
├── css/
├── js/
└── views/
routes/
├── api.php
├── web.php
└── console.php
storage/
tests/
├── Feature/
├── Support/
└── Unit/
```

**Architekturregel:** Die öffentliche Resolver-Route `/{codeOrAlias}` ist die letzte passende Web-Route. Alle Systempfade und Alias-Reservierungen müssen davor aufgelöst werden.

---

## 5. Schnittstellen (HTTP + Webhooks)

Standard-Webrouten für Login, Passwort-Reset, E-Mail-Verifikation und Logout werden über das offizielle Laravel Starter Kit bereitgestellt. Im Pflichtenheft werden zusätzlich die projektspezifischen Endpunkte definiert.

### 5.1 HTTP-Endpunkte

| Methode | Pfad | Beschreibung | Auth | Meilenstein |
|---|---|---|---|---|
| `GET` | `/health` | Health-Check | Nein | M0 |
| `GET` | `/dashboard` | Nutzer-Dashboard | Session | M2 |
| `GET` | `/qr-codes/create` | Creator mit Typauswahl und dynamischem Formular | Session | M2 |
| `GET` | `/qr-codes/{code}` | QR-Code-Detailansicht mit Vorschau, Download und Status | Session, Besitzer oder Admin | M2 |
| `GET` | `/qr-codes/{code}/edit` | QR-Code bearbeiten | Session, Besitzer oder Admin | M2 |
| `GET` | `/nova` | Admin-Panel | Admin | M2 |
| `POST` | `/api/qr` | QR-Code erstellen | Session | M1 |
| `GET` | `/api/qr/{code}` | QR-Code-Daten abrufen | Session, Besitzer oder Admin | M1 |
| `PATCH` | `/api/qr/{code}` | QR-Code aktualisieren | Session, Besitzer oder Admin | M1 |
| `DELETE` | `/api/qr/{code}` | QR-Code löschen | Session, Besitzer oder Admin | M1 |
| `GET` | `/api/qr/{code}/download.svg` | Kanonischen SVG-Download erzeugen | Session, Besitzer oder Admin | M1 |
| `GET` | `/api/qr/{code}/download.png` | Kanonischen PNG-Download erzeugen | Session, Besitzer oder Admin | M1 |
| `GET` | `/api/dashboard/qr-codes` | Eigene QR-Codes listen | Session | M2 |
| `GET` | `/api/qr/{code}/stats` | Statistiken abrufen | Session, Besitzer oder Admin | M2 |
| `POST` | `/api/billing/checkout` | Stripe Checkout starten | Session | M2 |
| `POST` | `/api/billing/portal` | Stripe Customer Portal öffnen | Session | M2 |
| `POST` | `/api/billing/webhook` | Stripe Webhook | Stripe-Signatur | M2 |
| `GET` | `/{codeOrAlias}` | Öffentliche QR-Auflösung | Nein | M1 |

### 5.2 API-Request/Response-Beispiele

**POST /api/qr — QR-Code erstellen (Beispiel Pro/Business):**

```json
// Request
{
  "type": "url",
  "content": {
    "url": "https://example.com"
  },
  "alias": "mein-link",
  "expires_at": "2026-12-31T23:59:59Z",
  "burn": false,
  "max_scans": 100
}

// Response (201 Created)
{
  "id": 42,
  "code": "a7f3x2",
  "type": "url",
  "entitlement_plan": "pro",
  "resolve_url": "https://qrm.sg/a7f3x2",
  "alias": "mein-link",
  "expires_at": "2026-12-31T23:59:59Z",
  "burn": false,
  "max_scans": 100,
  "created_at": "2026-04-28T16:00:00Z"
}
```

**GET /api/qr/{code}/stats — Statistiken:**

```json
{
  "id": 42,
  "code": "a7f3x2",
  "type": "url",
  "scan_count": 42,
  "stats": {
    "total": 42,
    "today": 5,
    "lastScan": "2026-04-28T15:30:00Z"
  },
  "recent_scans": [
    {
      "id": 123,
      "device_type": "mobile",
      "os": "iOS",
      "browser": "Safari",
      "country": null,
      "scanned_at": "2026-04-28T15:30:00Z"
    }
  ]
}
```

### 5.3 Fehlerbehandlung

| HTTP-Status | Bedeutung | Beispiel |
|---|---|---|
| `401` | Nicht authentifiziert | Session fehlt oder ist abgelaufen |
| `403` | Keine Berechtigung | Fremden QR-Code bearbeiten |
| `404` | Nicht gefunden | QR-Code oder Alias existiert nicht |
| `409` | Konflikt | Alias bereits vergeben |
| `410` | Abgelaufen | QR-Code hat Ablaufdatum überschritten |
| `422` | Validierungsfehler | Fehlende Pflichtfelder, ungültiger Typ |
| `429` | Rate-Limit | Zu viele Requests |
| `500` | Serverfehler | Unerwarteter Fehler |

Fehler-Response-Format:
```json
{
  "message": "Die Anfrage ist ungültig.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "alias": [
      "Dieses Alias ist bereits vergeben."
    ]
  }
}
```

---

## 6. Datenmodell

### 6.1 Entity-Relationship-Diagramm

```
users ──────────────< qr_codes ─────────────< scans
  │                      │
  │                      ├───────────────────< qr_code_routes
  │                      ├───────────────────< scan_stats_hourly
  │                      └───────────────────< scan_stats_daily
  ├────────────────────< subscriptions
  ├────────────────────< personal_access_tokens (ab M4)
  └──────────────────── roles/permissions via Paket-Tabellen
```

### 6.2 Tabellen im Detail

#### `users` — Nutzeraccounts

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | Interne Nutzer-ID |
| `name` | TEXT | NOT NULL | Anzeigename |
| `email` | TEXT | UNIQUE, NOT NULL | Login-E-Mail |
| `email_verified_at` | TIMESTAMPTZ | NULLABLE | E-Mail verifiziert |
| `password_hash` | TEXT | NOT NULL | Argon2id-Hash |
| `status` | TEXT | NOT NULL, DEFAULT 'active' | `active`, `suspended`, `deleted` |
| `plan` | TEXT | NOT NULL, DEFAULT 'free' | Tarif: `free`, `pro`, `business` |
| `stripe_id` | TEXT | NULLABLE | Stripe-Kunden-ID |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Registrierung |
| `updated_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Letzte Änderung |

**Indexe:** `idx_users_email` (email), `idx_users_plan` (plan), `idx_users_stripe` (stripe_id)

#### `qr_codes` — QR-Code-Einträge

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | Interne QR-ID |
| `user_id` | BIGINT | FK → users(id), ON DELETE CASCADE, NOT NULL | Besitzer |
| `type` | TEXT | NOT NULL, CHECK (8 Typen) | QR-Code-Typ |
| `content` | JSONB | NOT NULL | Typ-spezifische Daten |
| `entitlement_plan` | TEXT | NOT NULL, CHECK (`entitlement_plan IN ('free', 'pro', 'business')`) | Unveränderlicher Tarifstand der Ressource bei Erstellung |
| `entitlement_snapshot` | JSONB | NOT NULL | Unveränderlicher Snapshot der ressourcenbezogenen Tarifrechte |
| `source_subscription_id` | BIGINT | FK → subscriptions(id), ON DELETE SET NULL, NULLABLE | Ursprüngliches Abo als Audit-Referenz |
| `access_password_hash` | TEXT | NULLABLE | Passwortschutz als Hash |
| `expires_at` | TIMESTAMPTZ | NULLABLE | Ablaufdatum |
| `burn` | BOOLEAN | NOT NULL, DEFAULT false | Einmal-Nutzung |
| `max_scans` | INTEGER | NULLABLE | Maximale Scans |
| `scan_count` | INTEGER | NOT NULL, DEFAULT 0 | Aktuelle Scan-Anzahl |
| `last_scanned_at` | TIMESTAMPTZ | NULLABLE | Zeitpunkt des letzten Scans |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT true | Aktiv-Flag |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Erstellungsdatum |
| `updated_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Letzte Änderung |

**Indexe:** `idx_qr_user`, `idx_qr_entitlement_plan`, `idx_qr_expires`, `idx_qr_active`, `idx_qr_type`, `idx_qr_content` (GIN), `idx_qr_entitlement_snapshot` (GIN)

`entitlement_plan` und `entitlement_snapshot` werden ausschließlich bei der Erstellung gesetzt. Ein Billing-Webhook darf sie weder bei Upgrade noch Downgrade verändern. Die Anwendung prüft ressourcenbezogene Features gegen den Snapshot des QR-Codes und kontobezogene beziehungsweise neu anzulegende Features gegen den aktuellen Tarif des Accounts.

Der versionierte Snapshot enthält mindestens:

```json
{
  "version": 1,
  "plan": "pro",
  "validity": "unlimited",
  "custom_alias": true,
  "password_protection": true,
  "branding": "none",
  "download_profile": "high_resolution",
  "analytics": "full",
  "custom_domain": false,
  "white_label": false
}
```

Business setzt zusätzlich `custom_domain` und `white_label` auf `true`. Free verwendet `validity: "30_days"`, `branding: "qrm.sg"`, `download_profile: "standard"` und `analytics: "basic"`. API-Tokens, Teamkapazität, Bulk-Funktionen und Supportstufe gehören ausdrücklich nicht in den Ressourcen-Snapshot, weil sie kontobezogene laufende Leistungen sind.

#### `qr_code_routes` — Gemeinsamer öffentlicher Pfadnamensraum

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | Interne Routen-ID |
| `qr_code_id` | BIGINT | FK → qr_codes(id), ON DELETE CASCADE, NOT NULL | Zugehöriger QR-Code |
| `host` | CITEXT | NOT NULL, DEFAULT 'qrm.sg' | Normalisierter öffentlicher Host |
| `slug` | CITEXT | NOT NULL | Öffentlicher Code oder Alias ohne führenden Slash |
| `kind` | TEXT | NOT NULL, CHECK (`kind IN ('code', 'alias')`) | Unveränderlicher Systemcode oder änderbarer Custom Alias |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Anlagezeitpunkt |
| `updated_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Letzte Änderung |

**Constraints und Indexe:**
- `UNIQUE (host, slug)` erzwingt einen gemeinsamen case-insensitiven Namensraum für Codes und Aliase je öffentlichem Host.
- `UNIQUE (qr_code_id, host, kind)` erlaubt je QR-Code und Host genau einen Systemcode und höchstens einen Alias.
- Jeder QR-Code besitzt für `qrm.sg` genau eine Route vom Typ `code`; deren Slug ist unveränderlich und mindestens sechs Zeichen lang.
- Eine Code-Route wird innerhalb derselben Transaktion wie `qr_codes` eingefügt. Bei einer Unique-Kollision erzeugt das Backend einen neuen Code und versucht die Reservierung begrenzt erneut.
- Eine Alias-Route wird transaktional angelegt oder ersetzt. Eine Unique-Kollision mit einem Code oder Alias liefert HTTP 409; sie darf nicht durch Lookup-Priorität aufgelöst werden.
- Reservierte Systempfade werden bereits vor dem Insert abgelehnt. Custom Domains erhalten durch `host` einen eigenen Namensraum, ohne Kollisionen auf `qrm.sg` zu erlauben.

Die API-Felder `code`, `alias` und `resolve_url` werden aus `qr_code_routes` abgeleitet. Es gibt keine zweite, widersprüchliche Quelle dieser Werte in `qr_codes`.

#### `scans` — Scan-Protokoll

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | Eindeutige Scan-ID |
| `qr_code_id` | BIGINT | FK → qr_codes(id), ON DELETE CASCADE | Zugehöriger QR-Code |
| `request_host` | TEXT | NOT NULL | Beim Scan verwendeter Host |
| `route_kind` | TEXT | NOT NULL, CHECK (`route_kind IN ('code', 'alias')`) | Aufruf über Systemcode oder Alias |
| `ip_hash` | TEXT | NULLABLE | Mit serverseitigem HMAC-Secret pseudonymisierte IP-Adresse |
| `user_agent` | TEXT | NULLABLE | Längenbegrenzter vollständiger User-Agent |
| `country` | TEXT | NULLABLE | Land (GeoIP) |
| `region` | TEXT | NULLABLE | Region/Bundesland (GeoIP) |
| `city` | TEXT | NULLABLE | Stadt (GeoIP) |
| `asn` | TEXT | NULLABLE | Autonomous System Number (GeoIP) |
| `device_type` | TEXT | NULLABLE | mobile/desktop/tablet |
| `os` | TEXT | NULLABLE | Betriebssystem |
| `browser` | TEXT | NULLABLE | Browser |
| `is_bot` | BOOLEAN | NOT NULL, DEFAULT false | Ergebnis der Bot-Erkennung |
| `referer` | TEXT | NULLABLE | Längenbegrenzter vollständiger HTTP Referer |
| `accept_language` | TEXT | NULLABLE | Längenbegrenzter Accept-Language-Header |
| `utm` | JSONB | NULLABLE | Allowlist der fünf unterstützten UTM-Parameter |
| `response_type` | TEXT | NOT NULL | Redirect, HTML, Download oder Fehlerseite |
| `http_status` | SMALLINT | NOT NULL | Ausgelieferter HTTP-Status |
| `response_time_ms` | INTEGER | NOT NULL | Serverseitig gemessene Antwortzeit |
| `scanned_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Scan-Zeitpunkt |
| `delete_after` | TIMESTAMPTZ | NOT NULL | Tarifabhängig berechneter Löschzeitpunkt des Rohscans |

**Indexe:** `idx_scans_qr`, `idx_scans_time`, `idx_scans_delete_after`, `idx_scans_country`, `idx_scans_device`, `idx_scans_utm` (GIN)

#### `scan_stats_hourly` — Aggregierte Stundenstatistiken

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `qr_code_id` | BIGINT | PK, FK → qr_codes(id) | QR-Code |
| `hour` | TIMESTAMPTZ | PK | Stundenbucket |
| `scan_count` | INTEGER | NOT NULL, DEFAULT 0 | Scans in dieser Stunde |
| `unique_ips` | INTEGER | NOT NULL, DEFAULT 0 | Eindeutige IP-Hashes |

#### `scan_stats_daily` — Aggregierte Tagesstatistiken

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `qr_code_id` | BIGINT | PK, FK → qr_codes(id) | QR-Code |
| `day` | DATE | PK | Datum |
| `scan_count` | INTEGER | NOT NULL, DEFAULT 0 | Scans an diesem Tag |
| `unique_ips` | INTEGER | NOT NULL, DEFAULT 0 | Eindeutige IPs |
| `top_country` | TEXT | NULLABLE | Häufigstes Land |
| `top_device` | TEXT | NULLABLE | Häufigstes Gerät |

#### `subscriptions` — Stripe-Abonnements

| Spalte | Typ | Constraints | Beschreibung |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | Interne Subscription-ID |
| `user_id` | BIGINT | FK → users(id), ON DELETE CASCADE | Zugehöriger Nutzer |
| `stripe_id` | TEXT | UNIQUE, NOT NULL | Stripe-Subscription-ID |
| `stripe_status` | TEXT | NOT NULL | Stripe-Status |
| `stripe_price` | TEXT | NOT NULL | Stripe-Preis-ID |
| `quantity` | INTEGER | NOT NULL, DEFAULT 1 | Anzahl |
| `trial_ends_at` | TIMESTAMPTZ | NULLABLE | Trial-Ende |
| `ends_at` | TIMESTAMPTZ | NULLABLE | Kündigungsende |
| `created_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Anlage |
| `updated_at` | TIMESTAMPTZ | NOT NULL, DEFAULT now() | Letzte Änderung |

**Zusätzliche Framework-/Paket-Tabellen:** `jobs`, `job_batches`, `failed_jobs`, `sessions`, `cache`, `password_reset_tokens`, `personal_access_tokens`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

---

## 7. Nicht-funktionale Anforderungen

### 7.1 Leistung

| Anforderung | Zielwert |
|---|---|
| Öffentliche QR-Auflösung mit warmem Cache | p95 < 50 ms App-Zeit |
| Öffentliche QR-Auflösung mit Cache-Miss | p95 < 150 ms App-Zeit |
| API-Antwortzeit für Dashboard/CRUD | p95 < 250 ms |
| Frontend-Ladezeit | < 2,5 s bis zur ersten nutzbaren Ansicht |
| Queue-Lag im Normalbetrieb | < 30 s |

### 7.2 Verfügbarkeit

| Anforderung | Zielwert |
|---|---|
| Uptime zum ersten Launch | 99% |
| Uptime nach Paid-Launch | 99,5% |
| Wiederherstellung | < 4 Stunden bei Ausfall |
| Backups | Täglich verschlüsselt, 30 Tage Aufbewahrung, mindestens eine Kopie außerhalb des Produktionsservers, Wiederherstellung getestet |

### 7.3 Sicherheit

| Maßnahme | Implementierung |
|---|---|
| Passwörter | Argon2id über Laravel Hashing |
| Web-Authentifizierung | Laravel Session Auth + CSRF |
| API-Authentifizierung | Laravel Sanctum Tokens |
| Rechteprüfung | Policies/Gates + Rollenmodell |
| Rate Limiting | Pro IP, pro Nutzer und pro Endpoint |
| Input-Validierung | Serverseitig über Form Requests |
| HTTPS | Let's Encrypt, automatisch |
| IP-Privacy | Keine Klartext-IP; HMAC-SHA-256 mit getrennt verwaltetem Secret und definierter Aufbewahrungsfrist |
| Trusted Proxies | Initial werden Client-IP-Header ausschließlich aus dem definierten Nginx-/Docker-Netz akzeptiert |
| Ausgabesicherheit | Nutzergenerierte Freitexte sind Klartext, werden kontextgerecht escaped und erlauben weder Markdown noch HTML |
| Analytics-Allowlist | Nur die in Abschnitt 3.3.1 definierten Felder; keine Cookies, Secrets, Authorization-Header oder beliebigen Request-Header |
| SQL-Injection | Eloquent/Query Builder, rohe SQL nur begründet und getestet |
| Webhooks | Stripe-Signaturprüfung + idempotente Verarbeitung |
| QR-Codes | Kryptografisch sicherer öffentlicher Code, reservierte Pfade geschützt |
| Passwortschutz | Optional für QR-Codes (Pro/Business), nur als Hash gespeichert |

### 7.4 Skalierbarkeit

| Stufe | QR-Codes | Infrastruktur | Kosten/Monat |
|---|---|---|---|
| Start | 0–100k | Ein VPS: Nginx, Laravel, PostgreSQL, Redis, Worker | Niedrig |
| Wachstum | 100k–1M | Getrennte App-, DB- und Redis-Rollen, zusätzlicher Worker | Mittel |
| Scale | 1M+ | Mehrere App-Instanzen, Managed PostgreSQL, dedizierte Worker | Hoch |

### 7.5 Wartbarkeit

| Anforderung | Umsetzung |
|---|---|
| Offizielle Ökosystem-Bausteine | Laravel, Cashier, Nova, Sanctum, Horizon |
| Klare Schichten | Controller schlank, Fachlogik in `app/Actions` und `app/Domain` |
| Reproduzierbares Deployment | Docker + definierte Umgebungsvariablen |
| Umgebungsvariablen | Alle Konfiguration über `.env` |
| Migrationsdisziplin | Jede Schemaänderung ausschließlich über Laravel-Migrationen |

### 7.6 Testanforderungen

| Testart | Mindestumfang | Verbindlichkeit |
|---|---|---|
| Unit-Tests | Domänenregeln, Planlogik, Validatoren, Resolver-Helfer | Pflicht |
| Feature-Tests | Auth, QR-CRUD, Resolve, Dashboard, Billing-Endpunkte | Pflicht |
| Integrationstests | Redis, Queue-Jobs, Scan-Löschung, lokale GeoIP-Datenbank, Mail, Stripe-Webhooks, Nova-relevante Prozesse | Pflicht bei externer Integration |
| Browser-Smoke-Tests | Login, QR erstellen, QR auflösen, Upgrade, Admin-Zugriff, Deutsch/Englisch und WCAG-Kernprüfung | Vor jedem Release |
| Regressionstests | Jeder Bugfix erhält mindestens einen automatisierten Test | Pflicht |

**Verbindliche Testregeln:**
- Alle automatisierten Tests laufen in CI gegen PostgreSQL und Redis.
- Kritische Release-Flows müssen testabgedeckt sein: Registrierung, Login, QR-Erstellung, QR-Auflösung, Tarifwechsel, Admin-Zugriff.
- Für `app/Domain`, `app/Actions` und `app/Jobs` wird eine Testabdeckung von mindestens 80% angestrebt; die Coverage ersetzt keine inhaltliche Prüfung.

### 7.7 Code-Qualität

| Bereich | Anforderung |
|---|---|
| Code Style | `laravel/pint` ohne Verstöße |
| Statische Analyse | `phpstan`/`larastan` mindestens Level 8 |
| Architektur | Keine Geschäftslogik in Blade-Views, Nova-Ressourcen oder dicken Controllern |
| Validierung | Jede schreibende HTTP-Schnittstelle nutzt eigene Form Requests |
| Autorisierung | Zugriffe auf fremde Ressourcen sind über Policies abgesichert |
| Nebenläufigkeit | Langlaufende oder externe Effekte laufen über Queue-Jobs |
| Datenbank | Jede neue Query mit Performance-Relevanz ist indiziert oder begründet |
| Build | `composer test`, statische Analyse, Formatter-Check und Frontend-Build sind grün |

---

## 8. Meilensteinplan

### M0 — Grundlagen-Skelett mit Laravel

| Aufgabe | Ergebnis |
|---|---|
| Laravel-Grundprojekt aufsetzen | Leeres, lauffähiges Laravel-Projekt mit PHP 8.3 |
| PostgreSQL, Redis und Docker konfigurieren | Lokale und Staging-Umgebung reproduzierbar |
| Offizielles Laravel Starter Kit integrieren | Login, Reset, Verify, Session-Auth verfügbar |
| Nova, Cashier, Sanctum und Rollenpaket einbinden | Produkt-Bausteine technisch vorbereitet |
| Zielstruktur anlegen | `app/Actions`, `app/Domain`, `app/Nova`, `tests/*` vorhanden |
| Quality Gates einrichten | Pint, PHPStan/Larastan, Tests, Frontend-Build, CI |
| Queue, Horizon, Mail und Health-Check aktivieren | Betriebsskelett bereit |

### M1 — Core-Logik

| Aufgabe | Ergebnis |
|---|---|
| Datenmodell und Migrationen umsetzen | `users`, `qr_codes`, `qr_code_routes`, `scans`, `scan_stats_*`, `subscriptions` angelegt |
| QR-Domänenlogik für 8 Typen umsetzen | Erstellen, Bearbeiten, Löschen, Validieren |
| Öffentlichen Resolver implementieren | `/{codeOrAlias}` funktioniert performant und sicher |
| Download-Generierung umsetzen | SVG und PNG serverseitig erzeugbar |
| Scan-Erfassung und Basiszähler implementieren | `scan_count`, `last_scanned_at`, Rohscans funktionieren |
| Eigentümer- und Admin-Autorisierung umsetzen | Policies greifen für alle Ressourcen |
| Gemeinsamen Routen-Namensraum absichern | Keine Kollision zwischen Code, Alias, Host und Systemrouten; parallele Reservierungen sind transaktionssicher |

### M2 — Produktfeatures

| Aufgabe | Ergebnis |
|---|---|
| Dashboard für Nutzer fertigstellen | Eigene QR-Codes, Detailansichten, Bearbeitung, Account |
| Free/Pro/Business-Regeln umsetzen | Limits und Feature-Freischaltungen greifen |
| Ressourcenbezogenen Bestandsschutz umsetzen | Jeder QR-Code besitzt einen unveränderlichen Berechtigungs-Snapshot; Downgrades verändern bestehende Ressourcen nicht |
| Stripe Checkout und Customer Portal integrieren | Upgrade, Kündigung, Rückkehr ins Dashboard |
| Webhook-Verarbeitung absichern | Tarifstatus ist synchron und idempotent |
| Admin-Panel in Nova modellieren | Nutzer-, QR- und Billing-Übersicht verfügbar |
| E-Mail-Flows umsetzen | Verify, Reset, Benachrichtigungen, Ablauf-Hinweise |
| Passwortgeschützte QR-Codes ausliefern | Zugriff mit Hash-Prüfung und sauberem UX-Flow |

### M3 — Analytics und Betriebsreife

| Aufgabe | Ergebnis |
|---|---|
| Stündliche und tägliche Aggregation implementieren | `scan_stats_hourly` und `scan_stats_daily` gepflegt |
| Vollständige Analytics-Allowlist erfassen | User-Agent, Referer, Sprache, UTM, GeoIP/ASN, Bot- und Resolverdaten werden nach Schema gespeichert |
| Rohscan-Aufbewahrung automatisieren | `delete_after` wird korrekt gesetzt; täglicher Löschjob arbeitet idempotent und beobachtbar |
| Analytics-Ansichten im Dashboard aufbauen | Zeitreihen, Geräte, Länder sichtbar |
| Redis-Caching und Invalidation vervollständigen | Resolver-Performance stabil |
| Backups, Monitoring und Fehlerverfolgung ergänzen | Release-Betrieb beherrschbar |
| Staging- und Release-Prozess dokumentieren | Rollout wiederholbar und nachvollziehbar |

### M4 — Business-Erweiterungen

| Aufgabe | Ergebnis |
|---|---|
| API-Tokens mit Scopes freischalten | Business-API nutzbar |
| Custom Domains einführen | Eigene Domains resolvebar und administrierbar |
| Team-Verwaltung mit Rollen umsetzen | Owner/Manager/Member funktionieren |
| White-Label-Optionen bereitstellen | Branding je Plan steuerbar |
| Bulk-Import/Export ergänzen | Kampagnenfähig |

---

## 8A. Meilenstein M5 — Produkt-Features v2 (UX-Analyse Juli 2026)

Auf Basis der UX-Analyse vom 11.07.2026 werden folgende Features als Ergänzung zu M0–M4 spezifiziert. Jedes Feature ist als eigenständiger Task nach dem Task-Template zu definieren.

### M5.1 — FEAT-01: QR-Code Naming & Dashboard-Anker

Jeder QR-Code erhält einen prominenten, nutzerdefinierten Anzeigenamen (`title`), der im Dashboard, in der Detailansicht und im Editor die primäre Identifikation ist. Der Kurzcode und Alias treten in den Hintergrund.

| Anforderung | Umsetzung |
|---|---|
| Dashboard-Spalte „Name" als erste Spalte | `title` wird primär, Code/Alias als Subtext |
| Detail-Seite zeigt Namen als `<h1>` | Controller übergibt `title` an View |
| Creator: Name-Feld verpflichtend, mit Default-Vorschlag | Livewire-Validierung `required` |

**Status:** ✅ Umgesetzt.

### M5.2 — FEAT-02: Analytics-Sichtbarkeit im Dashboard

Die Scan-Anzahl im Dashboard ist ein klickbarer Link zur Analytics-Seite. Zusätzlich erhält jede Zeile ein Analytics-Icon.

| Anforderung | Umsetzung |
|---|---|
| Scan-Zahl klickbar → `/qr-codes/{id}/analytics` | Blade: `<a>` um `scan_count` |
| Analytics-Icon pro Zeile | SVG-Icon in Actions-Spalte |

**Status:** ✅ Umgesetzt.

### M5.3 — FEAT-03: Anonyme QR-Erstellung ohne Registrierung

Besucher können ohne Registrierung einen URL-QR-Code erstellen. Ziel ist die Senkung der Einstiegshürde und Conversion in Registrierungen.

| Anforderung | Umsetzung |
|---|---|
| Landing Page CTA „Try Now" | Route `/create`, AnonymousCreator Livewire |
| Anonymer Nutzer erstellt 1 URL-QR-Code (24h gültig) | Gast-User, `expires_at = now+24h` |
| Nach Erstellung: Registrierungs-Hinweis mit Benefits | Conversion-Funnel-View |
| Bei Registrierung: Code ins Konto übernehmbar | TODO — Post-Registration-Hook |
| Wasserzeichen / qrm.sg-Branding auf anon. Codes | TODO — noch nicht implementiert |
| 24h-Cleanup-Job für abgelaufene Gast-Codes | TODO — noch nicht implementiert |

**Status:** ✅ Grundfunktion umgesetzt. Wasserzeichen + Cleanup-Job fehlen.

### M5.4 — FEAT-04: Visuelle QR-Anpassung

QR-Codes können visuell angepasst werden: Farben, Dot-Stile, Gradient, Logo und Error-Correction-Level.

| Anforderung | Umsetzung | Plan |
|---|---|---|
| Vordergrund-/Hintergrundfarbe | `QrStyleService` + Endroid Builder | Alle |
| Dot-Stile (Square, Round, Extra-Round) | `RoundBlockSizeMode` | Alle |
| Error-Correction L/M | Endroid Standard | Alle |
| Error-Correction Q/H | Endroid `ErrorCorrectionLevel::H` | Pro+ |
| Gradient (Von/Bis-Farbe + Winkel) | Midpoint-Color-Blending | Pro+ |
| Logo-Upload (PNG/SVG, resize) | Storage + Builder `setLogoPath` | Pro+ |
| Margin-Control (0–50px) | `setMargin` | Alle |
| **Design-Tab im Creator/Editor (UI)** | **FEHLT NOCH** | — |

**Status:** ✅ Backend + Service + Tests. ❌ **UI im Creator/Editor fehlt.**

### M5.5 — FEAT-05: Alias-Tiers & Monetarisierung

Custom Aliase werden nach Plan gestaffelt. Premium-Shortcodes (≤4 Zeichen) sind monetarisierbar.

| Tier | Alias-Regeln | Verfügbarkeit |
|---|---|---|
| Free | Zufälliger 6-Zeichen-Code; Custom Alias 8–32 Zeichen | Kostenlos |
| Pro | Custom Alias 4–32 Zeichen | Im Abo |
| Business | Premium-Alias 2–32 Zeichen | Zusätzliche Monetarisierung |

| Anforderung | Umsetzung |
|---|---|
| Backend: Mindestlängen je Plan | ✅ `EntitlementGate::featureFlags()` |
| Creator: Alias-Feld validiert nach Plan | ❌ **FEHLT** |
| Premium-Shortcode-Kauf-Flow (Stripe) | ❌ **FEHLT** |
| Reserved-Path-Schutz für 1–2 Zeichen | ❌ **FEHLT** |

**Status:** ✅ Backend. ❌ **UI-Validierung + Kauf-Flow fehlen.**

### M5.6 — FEAT-06: A/B Testing

Ein QR-Code kann mehrere Ziel-URLs haben (Varianten), die nach Strategie ausgespielt werden.

| Anforderung | Umsetzung |
|---|---|
| Migration `qr_code_variants` Tabelle | ✅ |
| VariantSelector (gewichtet, geräteabhängig) | ✅ |
| Resolver wählt Variante, tracked scan_count pro Variante | ✅ |
| Scan-Datensatz enthält `qr_code_variant_id` | ✅ |
| Entitlement-Gate: Pro+ only | ✅ |
| **Editor-UI zum Anlegen von Varianten** | ❌ **FEHLT** |
| **Analytics: Pro-Variante Metriken** | ⚠️ Teilweise |

**Status:** ✅ Backend + Tests. ❌ **Editor-UI fehlt.**

### M5 Abnahmekriterien

- [ ] FEAT-01: Dashboard zeigt Namen primär; Detail-Seite nutzt Namen als Titel
- [ ] FEAT-02: Scan-Zahl ist klickbar und führt zur Analytics-Seite
- [ ] FEAT-03: Anonymer Nutzer kann ohne Login QR erstellen; Conversion-Hinweis sichtbar
- [ ] FEAT-04: Design-Tab im Creator mit Farbe, Dot-Pattern, Logo (Pro+), Gradient (Pro+)
- [ ] FEAT-05: Creator zeigt Alias-Optionen abhängig vom Tier; Premium-Shortcode-Kauf möglich
- [ ] FEAT-06: Editor kann Varianten anlegen; Analytics zeigt pro-Variante Metriken
- [ ] Wasserzeichen auf anonymen QR-Codes sichtbar
- [ ] 24h-Cleanup-Job für Gast-Codes läuft idempotent

---

## 9. Abnahmekriterien

### 9.1 M0 — Grundlagen-Skelett mit Laravel

- [ ] `composer install`, `php artisan test`, Formatter-Check, statische Analyse und Frontend-Build laufen in einer sauberen Umgebung erfolgreich
- [ ] Login, Logout, Passwort-Reset und E-Mail-Verifikation funktionieren über das Laravel-Skelett
- [ ] PostgreSQL, Redis, Queue-Worker und Health-Check laufen lokal und in Staging reproduzierbar
- [ ] Nova ist installiert und ausschließlich für Admin-Nutzer erreichbar
- [ ] Die Zielstruktur des Projekts ist angelegt und wird in den ersten Tasks verbindlich verwendet

### 9.2 M1 — Core-Logik

- [ ] Alle 8 QR-Typen können erstellt, bearbeitet und gelöscht werden
- [ ] Jedes serverseitige SVG/PNG kodiert ausschließlich die unveränderliche Resolver-URL des Systemcodes; typ-spezifische Inhalte werden erst hinter dieser URL ausgeliefert
- [ ] Die öffentliche Auflösung funktioniert für Kurzcode und Alias korrekt
- [ ] Code und Alias teilen sich je Host einen case-insensitiven, datenbankseitig eindeutigen Namensraum; Systempfade und parallele Reservierungen kollidieren nicht
- [ ] Scan-Zählung, `last_scanned_at` und Ablaufdatum funktionieren; bei parallelen Zugriffen liefert Burn exakt einmal und `max_scans = N` höchstens N-mal aus
- [ ] SVG- und PNG-Download werden serverseitig erzeugt
- [ ] Zugriffe auf fremde QR-Codes werden per Policy verhindert
- [ ] Kernlogik ist durch Unit- und Feature-Tests abgesichert

### 9.3 M2 — Produktfeatures

- [ ] Nutzer können sich registrieren, verifizieren und einloggen
- [ ] Dashboard zeigt ausschließlich die eigenen QR-Codes des Nutzers
- [ ] Free-Tier-Limits werden technisch erzwungen; jeder im Free-Tier erstellte QR-Code läuft exakt 30 Tage nach Erstellung ab und nur tatsächlich aktive QR-Codes zählen gegen das Zehnerlimit
- [ ] Pro-Abonnement kann über Stripe abgeschlossen werden
- [ ] Der Plan wird erst nach validiertem Webhook aktualisiert
- [ ] Ein Downgrade ändert nur den aktuellen Account-Tarif; bestehende QR-Codes behalten Gültigkeit, Premium-Funktionen, Analytics und vorhandene Routen gemäß ihrem unveränderlichen Berechtigungs-Snapshot
- [ ] Grandfathered Pro-/Business-QR-Codes zählen nach einem Downgrade nicht gegen das Free-Limit; neu angelegte QR-Codes erhalten ausschließlich die Rechte des aktuellen Tarifs
- [ ] Neue kontobezogene Premium-Ressourcen und -Kapazitäten werden nach Abo-Ende abgelehnt, während bereits an QR-Codes gebundene Premium-Einstellungen funktionsfähig bleiben
- [ ] Admins können Nutzer sperren, entsperren und Pläne einsehen bzw. ändern
- [ ] Ablauf- und Account-bezogene E-Mails werden korrekt versendet

### 9.4 M3 — Analytics und Betriebsreife

- [ ] Analytics-Dashboard zeigt Länder, Geräte und Zeitverläufe
- [ ] Jeder Rohscan enthält ausschließlich die freigegebene Analytics-Allowlist; Klartext-IP, Cookies, Authorization-Header, Passwörter und Browser-Fingerprints fehlen nachweislich
- [ ] Rohscans von QR-Codes mit Free-Berechtigung werden 60 Tage nach QR-Erstellung und Rohscans mit Pro-/Business-Berechtigung 24 Monate nach dem jeweiligen Scan automatisch gelöscht
- [ ] Aggregationsjobs pflegen `scan_stats_hourly` und `scan_stats_daily`
- [ ] Redis-Caching beschleunigt die öffentliche QR-Auflösung messbar
- [ ] Tägliche verschlüsselte Backups mit 30 Tagen Aufbewahrung und externer Kopie sind vorhanden; ein Restore wurde erfolgreich getestet
- [ ] Monitoring und E-Mail-Alarmierung für Resolver, Queue, Datenbank, Backups und Stripe-Webhooks sind nachgewiesen
- [ ] Browser-Smoke-Tests laufen auf Deutsch und Englisch gegen Staging erfolgreich; WCAG-2.2-AA-Kernanforderungen sind automatisiert und manuell geprüft

### 9.5 M4 — Business-Erweiterungen

- [ ] API-Tokens mit Scopes funktionieren zuverlässig
- [ ] Custom Domains können sicher eingerichtet und aufgelöst werden
- [ ] Team-Rollen greifen für Business-Konten korrekt
- [ ] White-Label-Optionen werden planabhängig freigeschaltet
- [ ] Bulk-Import/Export funktioniert für größere Kampagnen

### 9.6 Gesamtprojekt-Abnahme

- [ ] Sämtliche Abnahmekriterien aus M0 bis M4 sind erfüllt; kein in diesem Pflichtenheft beschriebener Produktteil wurde stillschweigend aus dem Projektumfang entfernt
- [ ] Der vollständige Nutzerfluss Registrierung → QR-Code erstellen → SVG/PNG herunterladen → öffentlich auflösen → Inhalt ändern → denselben gedruckten QR-Code erneut auflösen → Analytics einsehen ist als Browser-Smoke-Test nachgewiesen
- [ ] Free-, Pro- und Business-Berechtigungen sind für Weboberfläche, HTTP-Schnittstellen, Downloads und Resolver konsistent serverseitig erzwungen
- [ ] Die verbleibenden Release-Freigaben aus Abschnitt 12 sowie die rechtliche Produktionsfreigabe sind entschieden und dokumentiert

---

## 10. Kostenstruktur

| Posten | Kosten | Kündbarkeit |
|---|---|---|
| Domain `qrm.sg` | ~15 EUR/Jahr | Jährlich kündbar |
| Hosting (Laravel App + Worker + Redis) | Abhängig von Zielumgebung | Monatlich kündbar |
| PostgreSQL | Im Start auf eigener Instanz oder als Managed Service | Monatlich kündbar |
| Laravel Nova Lizenz | Lizenzkosten laut Hersteller | Herstellerabhängig |
| Stripe | 0 EUR Grundgebühr | Jederzeit kündbar |
| E-Mail (Resend o.ä.) | 0 EUR bis 100 E-Mails/Tag | Jederzeit kündbar |

**Operative Basiskosten ohne Einmallizenzen starten niedrig, müssen aber vor Launch mit finalem Hosting- und Nova-Modell neu kalkuliert werden.**
**Break-Even ist nach finaler Kostenentscheidung für Hosting, Lizenzierung und Mail-Volumen separat zu berechnen.**

---

## 11. Risikoanalyse

| Risiko | Wahrscheinlichkeit | Auswirkung | Minimierung |
|---|---|---|---|
| Keine zahlenden Kunden | Mittel | Niedrig | Pay-per-QR ab 0,50 EUR als Impuls-Kauf |
| Rewrite verliert Fachlogik | Mittel | Hoch | Bestehende Deno-Fassung als Referenz, Regressionstests pro Kernfluss |
| Billing-Status driftet von Stripe weg | Mittel | Hoch | Webhook als Quelle der Wahrheit, idempotente Verarbeitung |
| Code oder Alias kollidiert mit einer öffentlichen oder systeminternen Route | Mittel | Hoch | Gemeinsame `qr_code_routes`-Tabelle, case-insensitiver Unique-Key je Host, reservierte Slugs und Resolver-Fallback als letzte Route |
| Resolver-Performance reicht nicht | Mittel | Mittel | Redis-Cache, Load-Tests, Profiling, gezielte Indizes |
| Datenschutz-Probleme | Niedrig | Hoch | Privacy-by-Design: IPs nur als HMAC-Pseudonym, minimale Datenerfassung und freigegebene Löschfristen |
| Ausfall der Plattform | Niedrig | Mittel | Backups, Queue-Überwachung, dokumentiertes Recovery |
| Vendor-Lock-in bei Nova/Stripe | Mittel | Mittel | Integrationen klar kapseln, Fallback-Optionen dokumentieren |

---

## 12. Produkt- und Betriebsentscheidungen

Dieser Abschnitt dokumentiert die getroffenen Produktentscheidungen und verbleibende Release-Freigaben. Eine später eingeholte rechtliche Prüfung darf die technische Entwicklung begleiten, ist aber vor einem öffentlichen Produktivstart verbindlich abzuschließen.

### 12.1 Missbrauch, Phishing und Sperrprozess

qrm.sg führt keine proaktive redaktionelle Liste verbotener Inhaltskategorien und keine allgemeine Inhaltsmoderation ein. Technisch schädliche QR-Codes können über ein öffentliches Meldeformular oder die Support-E-Mail gemeldet werden. Meldungen werden manuell durch einen Admin geprüft; bei einem konkreten Phishing-, Malware- oder Account-Kompromittierungsverdacht darf der Admin den QR-Code sofort vorläufig deaktivieren und den Grund revisionsfähig dokumentieren. Gesperrte Codes liefern keine Weiterleitung und können nach Klärung wieder freigegeben werden.

Zum ersten Launch wird kein externer URL-Reputationsdienst eingebunden. Die Prüfung ist als austauschbare spätere Integration vorzusehen, ohne Resolver oder QR-Domänenmodell daran zu koppeln. Verantwortliche Person, Support-Adresse und Reaktionszeit werden spätestens vor öffentlichem Launch als Betriebsdaten konfiguriert.

### 12.2 Datenschutz und Analytics-Aufbewahrung

Die vollständige Analytics-Allowlist aus Abschnitt 3.3.1 wird gespeichert. Eine möglichst hohe Datentiefe bedeutet nicht das Speichern von Secrets oder beliebigen Headern: Klartext-IP, Cookies, Authorization-Header, Passwörter und Browser-Fingerprints bleiben ausgeschlossen.

Für QR-Codes mit Free-Berechtigungs-Snapshot wird `delete_after` auf `created_at + 60 Tage` gesetzt. Für QR-Codes mit Pro- oder Business-Berechtigungs-Snapshot wird jeder Rohscan 24 Monate ab `scanned_at` aufbewahrt. Ein späterer Account-Downgrade verkürzt diese Frist nicht. Ein täglicher Löschjob entfernt fällige Rohscans idempotent und protokolliert Anzahl sowie Laufstatus. Aggregierte Statistiken bleiben bestehen, solange QR-Code und Account bestehen. Das Löschen eines QR-Codes oder Accounts entfernt über Foreign-Key-Cascades dessen Rohscans und Aggregationen unmittelbar.

GeoIP bis Land, Region, Stadt und ASN wird über eine lokale Datenbank ermittelt. Vollständiger Referer, Accept-Language, UTM-Parameter, vollständiger User-Agent, Bot-Erkennung und Resolver-Leistungsdaten werden gemäß Abschnitt 3.3.1 erfasst. Datenschutzkontakt, Datenschutzerklärung, Verarbeitungsdokumentation und rechtliche Freigabe bleiben ein verpflichtendes Produktions-Gate.

### 12.3 Darstellung nutzergenerierter Inhalte

`Message`, Event-Beschreibung, Contact-Notizen und vergleichbare Freitextfelder werden als Klartext gespeichert und bei HTML-Ausgabe kontextgerecht escaped. Markdown und freies HTML werden nicht unterstützt.

Oberfläche, E-Mails, Fehlerseiten und öffentliche Resolver-Seiten werden auf Deutsch und Englisch bereitgestellt. Die Auswahl folgt einer gespeicherten Nutzerpräferenz, danach der Browsersprache; für nicht unterstützte Sprachen ist Englisch der Fallback. Nutzergenerierte Inhalte werden nicht automatisch übersetzt.

Öffentliche Seiten und Kernflüsse müssen WCAG 2.2 AA erfüllen. Dazu gehören mindestens vollständige Tastaturbedienung, sichtbarer Fokus, ausreichende Kontraste, semantische Beschriftungen, verständliche Validierungsfehler und Screenreader-Unterstützung. Die Abnahme erfolgt automatisiert und durch einen manuellen Tastatur-/Screenreader-Smoke-Test.

### 12.4 Produktionsbetrieb und Verantwortlichkeiten

Die Anwendung wird zuerst als Docker-Compose-Stack gemäß Abschnitt 4.1.1 bereitgestellt. Zum ersten Launch ist nur der im Stack enthaltene Nginx als Reverse Proxy vorgesehen; ein CDN gehört nicht zum initialen Umfang. Laravel vertraut ausschließlich dem definierten Nginx-/Docker-Netz und nicht pauschal allen Forwarded-Headern.

Die Datenbank wird täglich verschlüsselt gesichert. Sicherungen werden 30 Tage aufbewahrt; mindestens eine Kopie liegt außerhalb des Produktionsservers. Ein automatisierter Backup-Job und ein dokumentierter, erfolgreich getesteter Restore sind Pflicht.

Resolver-, Queue-, Datenbank-, Backup- und Stripe-Webhook-Fehler lösen E-Mail-Alarme an eine über Umgebungsvariable konfigurierte Betreiberadresse aus. Der konkrete Zielserver, seine Region, die Betreiberadresse und der Datenschutzkontakt dürfen für Entwicklung offenbleiben, müssen aber vor öffentlichem Launch eingetragen sein.

Ein öffentlicher Produktivstart ist erst erlaubt, wenn Healthchecks, Monitoring, Fehlerverfolgung, externes Backup und Restore-Test erfolgreich abgenommen wurden.

### 12.5 Tarifwechsel und bestehende QR-Codes

Für während eines Pro- oder Business-Abos erstellte QR-Codes gilt dauerhafter ressourcenbezogener Bestandsschutz gemäß Abschnitt 3.5.3. Auch bei mehr als zehn vorhandenen Codes bleiben diese aktiv und zählen nicht gegen das spätere Free-Limit. Unbegrenzte QR-Codes erhalten beim Downgrade kein Ablaufdatum. Custom Alias, Passwortschutz, bestehende Custom-Domain-Routen, Branding/White-Label, Downloadqualität und Analytics-Umfang bleiben für diese QR-Codes erhalten.

Das Dashboard kennzeichnet den ursprünglichen Berechtigungsstand jeder Ressource transparent. Neue QR-Codes und neue kontobezogene Premium-Ressourcen richten sich ausschließlich nach dem aktuellen Account-Tarif. Webhooks, Resolver, Dashboard, Downloads, Analytics und E-Mails müssen dieselbe Trennung zwischen aktuellem Tarif und Ressourcen-Snapshot verwenden.

---

### 12.6 Analytics-UI und Tooling-Entscheidung

Für die Analytics-Oberfläche wird **kein eigenes Chart-/BI-System neu entwickelt**. Stattdessen werden vorhandene freie Bausteine verwendet:

- **Filament Widgets** für KPI-Kacheln, Tabellen und Dashboard-Integration
- **Chart.js** für Zeitreihen und einfache Diagramme
- bestehende Laravel- und Filament-Standards für Filter, Perioden und Detailansichten

Ziel ist eine solide, wartbare Standardlösung ohne Vendor-Lock-in und ohne zusätzliche Lizenzkosten. Die fachliche Quelle der Wahrheit bleibt das bestehende Scan-/Aggregationsmodell; die UI ist nur die Darstellungsschicht.

### 12.7 Dev/Test-Ausnahme für E-Mail-Verifikation

Für lokale Entwicklung und explizit freigeschaltete Test-/Staging-Umgebungen darf die E-Mail-Verifikation via `APP_SKIP_EMAIL_VERIFICATION=true` umgangen werden. In CI und in produktionsnahen Tests bleibt die Verifikationslogik aktiv, damit die regulären Auth- und Security-Tests nicht verwässert werden.

### 12.8 Feature-Roadmap v2 (Juli 2026)

Auf Basis der UX-Analyse wurden folgende Features definiert und teilweise umgesetzt:

| Feature | Status | Sprint |
|---|---|---|
| **FEAT-01:** QR-Code Naming als Dashboard-Anker | ✅ Umgesetzt | Sprint 1 |
| **FEAT-02:** Analytics-Sichtbarkeit im Dashboard | ✅ Umgesetzt | Sprint 1 |
| **FEAT-03:** Anonyme QR-Erstellung ohne Registrierung | ✅ Umgesetzt | Sprint 2 |
| **FEAT-04:** Visuelle QR-Anpassung (Farbe, Logo, Dots) | ✅ Umgesetzt | Sprint 3 |
| **FEAT-05:** Alias-Tiers & Premium-Shortcodes | ✅ Backend umgesetzt | Sprint 4 |
| **FEAT-06:** A/B Testing für QR-Ziel-URLs | 🔨 In Arbeit | Sprint 5 |

**FEAT-03 Details (Anonyme Erstellung):**
- Landing Page bietet „Try Now — No Sign-Up" CTA
- Anonymer Nutzer kann 1 URL-QR-Code erstellen
- Code ist 24h gültig, danach automatische Löschung
- Nach Erstellung erscheint Conversion-Funnel mit Registrierungs-Hinweis
- Wasserzeichen/Branding ist für zukünftige Implementierung vorgesehen

**FEAT-05 Details (Alias-Tiers):**
| Tier | Alias-Regeln | Verfügbarkeit |
|---|---|---|
| Free | Zufälliger 6-Zeichen-Code; Custom Alias 8–32 Zeichen | Kostenlos |
| Pro | Custom Alias 4–32 Zeichen | Im Abo inklusive |
| Business/Premium | Premium-Alias 2–32 Zeichen | Zusätzliche Monetarisierung |

Premium-Shortcodes (≤4 Zeichen) sind als **einmaliger Kauf (1,00 €)** für alle Nutzer verfügbar — inklusive Free, Pro und Business. Business-Nutzer erhalten Premium-Aliase kostenlos im Abo.

**Kauf-Flow:**
1. Nutzer gibt einen Alias mit ≤4 Zeichen im Creator/Editor ein.
2. UI zeigt: "Premium-Shortcode — für 1,00 € freischalten."
3. Klick auf "Kaufen" → Stripe Checkout (einmalige Zahlung, 1,00 €).
4. Nach Zahlung: Alias ist permanent für diesen Nutzer reserviert.
5. Der Alias kann für beliebig viele QR-Codes genutzt werden (immer derselbe Alias-Wechsel).

**Technische Umsetzung:**
- Tabelle: `premium_alias_purchases` (user_id, alias, stripe_checkout_session_id, status, paid_at)
- Eindeutige Reservierung: Ein Alias kann nur einmal gekauft werden (Unique-Constraint).
- Stripe-Produkt: `STRIPE_PRICE_PREMIUM_ALIAS` (einmalige Zahlung, 1,00 €, EUR).
- Webhook: Markiert Kauf als 'paid' bei erfolgreichem `checkout.session.completed`.
- API: `POST /billing/premium-alias/checkout` { alias: "abc" } → Stripe Checkout URL.
- Die Alias-Verfügbarkeitsprüfung in Creator/Editor/Service berücksichtigt gekaufte Aliase.

**FEAT-04 Details (Visuelle Anpassung):**
- Farben: Vorder- und Hintergrundfarbe wählbar (alle Pläne)
- Dot-Stile: Square, Round, Extra-Round (alle Pläne)
- Error-Correction: L/M für alle, Q/H für Pro+
- Gradient: Von/Bis-Farbe mit Winkel (Pro+)
- Logo: Upload, wird in die Mitte platziert (Pro+)
- Margin-Control: Quiet-Zone 0–50px
- Backend: `QrStyleService` als zentrale Instanz mit Entitlement-Gating
- Speicherung im bestehenden `settings` JSON-Feld — keine Migration nötig

---

---

### 12.9 Feature-Roadmap v2 — Erweiterung (Juli 2026)

Auf Basis der Produktevaluierung wurden zusätzliche Features definiert, die vor dem produktiven Launch umgesetzt werden sollen.

#### Übersicht

| Feature | Code | Status | Priorität | Abhängigkeiten |
|---|---|---|---|---|
| E-Mail-Verifikation deaktivierbar | DEV-EMAIL-SKIP | ✅ Umgesetzt | Hoch | Keine |
| Zahlungsanbieter-Integration | PAYMENT-01 | 🔨 Teilweise | Hoch | FEAT-08 |
| QR-Code-Versionshistorie | FEAT-07 | ❌ Offen | Mittel | Keine |
| Fair-Use-Policy & AGB | FEAT-08 | ❌ Offen | Hoch | Keine |
| Branding & Eigenwerbung | FEAT-09 | ✅ Umgesetzt | Mittel | Keine |

---

#### DEV-EMAIL-SKIP: E-Mail-Verifikation in der Entwicklungsphase deaktivieren

Solange qrm.sg nicht produktiv ist, wird die E-Mail-Verifikation über `APP_SKIP_EMAIL_VERIFICATION=true` in der `.env` umgangen. Das Middleware `SkipEmailVerification` setzt `email_verified_at` automatisch beim Login, sodass alle Funktionen sofort nutzbar sind.

In CI und produktionsnahen Tests bleibt die Verifikationslogik aktiv (siehe §12.7).

**Status:** ✅ Umgesetzt — Middleware, Config und `.env` konfiguriert.

---

#### PAYMENT-01: Zahlungsanbieter-Integration (Stripe via Laravel Cashier)

Stripe ist via Laravel Cashier (`laravel/cashier` v16.6) bereits integriert. Es existieren Billing-Controller (Checkout, Customer Portal, Webhook), Migrations (Subscriptions, Webhook-Events) und das Entitlement-Snapshot-Modell für Bestandsschutz.

**Noch offen:**

| Aufgabe | Beschreibung |
|---|---|
| Stripe-Keys konfigurieren | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` in `.env` |
| Stripe-Produkte anlegen | Pro (5€/Monat), Business (19€/Monat) im Stripe-Dashboard |
| Price-IDs hinterlegen | `STRIPE_PRO_PRICE_ID`, `STRIPE_BUSINESS_PRICE_ID` |
| Checkout-Flow testen | End-to-End mit Test-Karte 4242 4242 4242 4242 |
| Webhook-Handling prüfen | `stripe listen --forward-to localhost/api/billing/webhook` |
| Customer Portal testen | Karten verwalten, kündigen, Rechnungen |
| Premium-Short-URLs | Monetarisierung von ≤4-Zeichen-Aliasen |

**Premium-Short-URL-Monetarisierung:**

| Alias-Länge | Preis | Verfügbarkeit |
|---|---|---|
| 2–3 Zeichen | 9€/Monat (zusätzlich) | Business only |
| 4–7 Zeichen | Im Abo inklusive | Pro / Business |
| 8–32 Zeichen | Kostenlos | Alle Pläne |

**Abo-Modell-Integration:** Der Stripe-Webhook ist die einzige Quelle der Wahrheit für bezahlte Features (§3.5.1). Ein erfolgreicher Webhook aktualisiert den User-Plan und den Entitlement-Snapshot für neu erstellte QR-Codes. Bestehende QR-Codes behalten ihren ursprünglichen Snapshot (§3.5.3).

**Status:** 🔨 Teilweise umgesetzt — Code vorhanden, Stripe-Verbindung nicht aktiv.

---

#### FEAT-07: QR-Code-Versionshistorie

Jede Änderung an einem QR-Code wird mit Versionssnapshot gespeichert. Der Nutzer kann frühere Versionen einsehen und wiederherstellen.

**Datenmodell — neue Tabelle `qr_code_revisions`:**

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | BIGINT (PK) | Revision-ID |
| `qr_code_id` | BIGINT (FK → qr_codes) | Zugehöriger QR-Code |
| `user_id` | BIGINT (FK → users) | Wer hat die Änderung durchgeführt |
| `version` | INT | Monoton steigend pro QR-Code |
| `snapshot` | JSONB | Vollständiger Snapshot (content, title, settings, alias, status) |
| `change_summary` | TEXT | Auto-generierte Kurzbeschreibung |
| `created_at` | TIMESTAMPTZ | Zeitpunkt der Änderung |

**Anforderungen:**

1. Bei jedem Speichern im Editor wird der **vorherige** Zustand als Revision gespeichert
2. Der Snapshot enthält: `content`, `title`, `settings`, `alias`, `status`
3. Der Editor erhält einen "History"-Bereich mit Timeline, Diff-Anzeige und Restore-Button
4. Wiederherstellung setzt `content`, `title`, `settings` zurück (nicht `type`, nicht `route`)
5. Resolver-Cache wird bei Restore invalidiert
6. Maximal 50 Revisionen pro QR-Code (älteste werden bereinigt)

**Status:** ❌ Nicht umgesetzt — keine Migration, kein Model, keine UI.

**Priorität:** Mittel

---

#### FEAT-08: Fair-Use-Policy & AGB

**Fair-Use-Limits** verhindern Missbrauch und definieren die nutzbaren Ressourcen pro Tarif:

| Limit | Free | Pro | Business |
|---|---|---|---|
| Aktive QR-Codes | 10 | 500 (Fair-Use) | 5.000 (Fair-Use) |
| Scans/Tag (pro Code) | Unbegrenzt | 50.000 | 500.000 |
| API-Requests/Minute | — | 60 | 300 |
| A/B-Varianten pro Code | 0 | 5 | 20 |
| Custom Aliases | 0 | Unbegrenzt (4–32 Zeichen) | Unbegrenzt (2–32 Zeichen) |

**Reaktion bei Überschreitung:**

| Stufe | Trigger | Aktion |
|---|---|---|
| Soft-Limit | Limit erreicht | Warn-E-Mail an Nutzer + Admin-Benachrichtigung |
| Hard-Limit | 2× überschritten | Code wird deaktiviert, Upgrade-Prompt |
| API | Rate-Limit überschritten | HTTP 429 mit `Retry-After` Header |

**Missbrauchsschutz (Rate-Limiting):**

| Endpoint | Limit | Scope |
|---|---|---|
| QR-Code-Erstellung | 20/Stunde (Free: 5) | Pro User |
| Resolver (Scans) | 100/Minute | Pro IP-Hash |
| Suspicious Activity | >500 Codes/24h von einer IP | Admin-Flag |

**AGB:** Ein rechtliches AGB-Dokument wird als statische Seite unter `/agb` (DE) und `/terms` (EN) bereitgestellt. Inhalt: Leistungsbeschreibung, Nutzerpflichten, Fair-Use-Policy mit Limits, Zahlungsbedingungen, Kündigung/Bestandsschutz, Haftungsausschluss, Datenschutz-Verweis, Gerichtsstand.

**Status:** ❌ Nicht umgesetzt — keine Limits, kein Rate-Limiting, keine AGB-Seite.

**Priorität:** Hoch (vor Produktivstart)

---

#### FEAT-09: Branding & Eigenwerbung auf Resolver-Seiten

Wenn ein Scanner einen qrm.sg-QR-Code scannt und auf der Resolver-Seite landet, wird tier-abhängiges Branding eingeblendet:

| Tier | Resolver-Seite |
|---|---|
| **Free** | "Powered by qrm.sg" Footer mit Mini-Logo + dezenter Hinweis "Create your own dynamic QR codes for free → Get started" |
| **Pro** | Sehr dezentes qrm.sg-Logo (nur Icon, kein Werbetext) |
| **Business** | White-Label: kein sichtbares qrm.sg-Branding |

Steuerung über `qrCode.entitlementSnapshot()->plan()` im `qr-types/layout.blade.php`.

**Status:** ✅ Umgesetzt — Layout, i18n DE/EN, Browser verifiziert.

**Priorität:** Mittel

---

#### FEAT-10: Vollständige API-Unterstützung und KI-Agenten-Bedienbarkeit

**Ziel:** Die REST-API soll von KI-Agenten und externen Systemen ohne Vorwissen nutzbar sein. Alle Endpunkte sind maschinenlesbar dokumentiert, QR-Typ-Schemata sind maschinell abrufbar, und Rate-Limits sind transparent.

**Tarif-Regelung:**
- **Free:** Kein API-Zugang
- **Pro:** API-Zugang als optionales Add-on (+1 EUR/Monat). Token-Erstellung im Account freischaltbar.
- **Business:** API-Zugang inklusive, kein Aufpreis

**Voraussetzung:** API-Token-Erstellung im Account-Bereich erfordert aktivierten API-Zugang (Pro-Add-on oder Business). Das Backend prüft bei Token-nutzung, ob der Nutzer API-Berechtigung hat. Bei Pro ohne Add-on wird HTTP 403 zurückgegeben.

### 10.1 OpenAPI/Scribe-Dokumentation (Muss)

- Die gesamte API wird mit Scribe (Laravel-Paket) oder alternativ manuell als OpenAPI 3.1 YAML dokumentiert
- Die Doku wird unter `GET /api/docs` als HTML und `GET /api/openapi.json` als maschinenlesbares JSON ausgeliefert
- Jeder Endpunkt enthält: Method, Path, Request-Body-Schema, Response-Schema, Fehler-Codes, Beispiel-Request, Beispiel-Response
- Auth-Endpunkte sind als `Bearer JWT` markiert
- Die Doku wird automatisch aus Code-Annotationen generiert, nicht manuell gepflegt
- Ein KI-Agent, der nur `/api/openapi.json` aufruft, kann die gesamte API nutzen

### 10.2 QR-Typ-Schema-Endpoint (Muss)

- `GET /api/qr-types` liefert alle 8 QR-Typen mit ihren Felddefinitionen
- Pro Typ: `id`, `label`, `description`, `icon`, `fields[]` mit `key`, `label`, `type` (text/url/textarea/select/checkbox/datetime-local/number/email), `required`, `maxlength`, `placeholder`, `options[]` für Selects
- Ein KI-Agent kann daraus automatisch gültige `POST /api/qr-codes` Requests konstruieren
- Beispiel-Response:

```json
{
  "types": [
    {
      "id": "url",
      "label": "URL",
      "description": "Weiterleitung zu einer Website",
      "fields": [
        { "key": "url", "label": "Destination URL", "type": "url", "required": true, "maxlength": 2048 }
      ]
    },
    {
      "id": "wifi",
      "label": "WiFi",
      "fields": [
        { "key": "ssid", "label": "Netzwerkname", "type": "text", "required": true, "maxlength": 32 },
        { "key": "encryption", "label": "Verschlüsselung", "type": "select", "required": true, "options": { "WPA": "WPA/WPA2", "WEP": "WEP", "none": "Keine" } }
      ]
    }
  ]
}
```

### 10.3 API-Index-Endpoint (Muss)

- `GET /api` liefert eine kompakte Übersicht aller verfügbaren Endpunkte
- Pro Endpunkt: `method`, `path`, `description`, `auth_required`, `scopes[]`
- Format:

```json
{
  "name": "qrm.sg API",
  "version": "1.0",
  "endpoints": [
    { "method": "GET", "path": "/api/qr-types", "description": "Alle QR-Typen mit Schemata", "auth_required": false },
    { "method": "POST", "path": "/api/qr-codes", "description": "QR-Code erstellen", "auth_required": true, "scopes": ["qr:write"] },
    { "method": "GET", "path": "/api/qr-codes/{id}/stats", "description": "Scan-Statistiken", "auth_required": true, "scopes": ["stats:read"] }
  ]
}
```

### 10.4 Rate-Limit-Header (Soll)

- Jede API-Antwort enthält standardisierte Rate-Limit-Header:
  - `X-RateLimit-Limit`: Maximale Requests pro Minute für diesen Endpunkt
  - `X-RateLimit-Remaining`: Verbleibende Requests im aktuellen Fenster
  - `X-RateLimit-Reset`: Unix-Timestamp des nächsten Reset-Zeitpunkts
- Bei HTTP 429 zusätzlich: `Retry-After` Header (Sekunden bis Reset)
- Rate-Limits pro Tarif: Pro 60/Min, Business 300/Min (gemäß FEAT-08)
- Implementiert via Laravel `RateLimiter` + Middleware

### 10.5 Analytics-API-Endpoints (Soll)

- `GET /api/qr-codes/{id}/stats` — Gesamtstatistiken + letzte 20 Scans
- `GET /api/qr-codes/{id}/stats/daily` — Tägliche Aggregationen
- `GET /api/qr-codes/{id}/stats/hourly` — Stündliche Aggregationen
- Auth: Token mit `stats:read` Scope, Besitzer oder Admin
- Response-Format analog §3.3.2

### 10.6 API-Berechtigungsprüfung (Muss)

- Beim Erstellen eines API-Tokens prüft das Backend den Nutzer-Tarif:
  - Free → 403 "API-Zugang erfordert Pro mit API-Add-on oder Business"
  - Pro ohne API-Add-on → 403 mit Upgrade-Hinweis
  - Pro mit API-Add-on → Token mit konfigurierten Scopes
  - Business → Token mit allen Scopes
- Stripe-Produkt für das Pro-API-Add-on: `pro_api_addon` (+1 EUR/Monat)
- Die Berechtigungsprüfung läuft bei jeder API-Anfrage mit Bearer-Token

### 10.7 Strukturierte Fehler-Responses (Muss)

- Alle Fehler-Responses folgen einheitlichem Format:
  - `message`: Menschlich-lesbare Fehlerbeschreibung
  - `code`: Maschinenlesbarer Fehlercode (z.B. `VALIDATION_ERROR`, `RATE_LIMIT_EXCEEDED`, `API_ACCESS_DENIED`, `FREE_TIER_LIMIT_EXCEEDED`)
  - `errors` (optional): Feld-spezifische Validierungsfehler
- HTTP-Status-Codes konsistent gemäß §5.3

---

### 12.10 Deployment-Architektur — Docker (Juli 2026)

Die gesamte Anwendung wird in Docker-Containern betrieben. Ziel ist ein reproduzierbarer, versionierter und schnell aktualisierbarer Deployment-Prozess.

#### Architektur

```
┌── Docker Host (10.0.0.102) ─────────────────────────────────┐
│                                                               │
│  ┌── qrm.sg-app (Container) ──────────────────────────────┐  │
│  │                                                         │  │
│  │  Supervisor                                              │  │
│  │    ├─ Nginx :80/:443                                    │  │
│  │    ├─ PHP-FPM                                           │  │
│  │    ├─ Queue Worker (php artisan queue:work --redis)     │  │
│  │    └─ Cron Scheduler (php artisan schedule:run)         │  │
│  │                                                         │  │
│  │  Redis (Cache + Queue + Session)                        │  │
│  │                                                         │  │
│  │  /var/www/qrm.sg  ← gemountetes Volume                  │  │
│  │    (Git-Working-Tree, persistente Dateien)              │  │
│  │                                                         │  │
│  └──────────────────┬──────────────────────────────────────┘  │
│                     │ Volume (persistent)                      │
│  ┌── qrm.sg-db (Container) ───────────────────────────────┐  │
│  │                                                         │  │
│  │  PostgreSQL 16                                          │  │
│  │    Datenbank: qrm_sg                                    │  │
│  │    Volume: pg_data (persistent, überlebt Rebuilds)      │  │
│  │                                                         │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

#### Design-Entscheidungen

| Entscheidung | Begründung |
|---|---|
| **Ein App-Container** mit Supervisor | Vereinfacht Deployment, eine Einheit, kein Docker-Netzwerk-Overhead |
| **PostgreSQL separat** | Daten überleben App-Rebuilds; DB kann unabhängig skaliert/gebackupt werden |
| **Redis im App-Container** | Cache + Queue + Session in einem Prozess; geringer Overhead |
| **Code als Volume (git pull)** | Hermes kann in den Container exec'en und `git pull` ausführen → sofortiger Code-Update ohne Rebuild |
| **Supervisor statt einzelner Dienste** | Ein Prozessmanager regelt Nginx, PHP-FPM, Queue-Worker und Scheduler |

#### Update-Strategie (Zero-Downtime Deploy)

Updates erfolgen durch Hermes direkt im laufenden Container, ohne Neustart:

| Schritt | Befehl | Dauer |
|---|---|---|
| 1. Code pullen | `cd /var/www/qrm.sg && git pull origin main` | ~2s |
| 2. Dependencies | `composer install --no-dev --optimize-autoloader` (nur bei Änderung) | ~5s |
| 3. Frontend | `npm ci && npm run build` (nur bei Asset-Änderung) | ~10s |
| 4. Migrations | `php artisan migrate --force` | ~1s |
| 5. Cache warmen | `php artisan config:cache && php artisan route:cache && php artisan view:cache` | ~1s |
| 6. Worker restart | `php artisan queue:restart` (Worker holen neuen Code beim nächsten Job) | sofort |

**Gesamt: ~10-20 Sekunden.** Aktive Requests laufen auf dem alten Code zu Ende; neue Requests bekommen den neuen Code. Kein Container-Neustart, keine Downtime.

#### docker-compose.yml Struktur

```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - qr-code-data:/var/www/qrm.sg
      - pg-socket:/var/run/postgresql
    depends_on:
      - db
    environment:
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - REDIS_HOST=127.0.0.1
      - APP_ENV=production

  db:
    image: postgres:16-alpine
    volumes:
      - pg-data:/var/lib/postgresql/data
    environment:
      - POSTGRES_DB=qrm_sg
      - POSTGRES_USER=qrm
      - POSTGRES_PASSWORD=${DB_PASSWORD}
    restart: always

volumes:
  qr-code-data:
  pg-data:
```

#### Umgebungsvariablen (Production)

| Variable | Wert |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://qrm.sg` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `db` |
| `CACHE_STORE` | `redis` |
| `SESSION_DRIVER` | `redis` |
| `QUEUE_CONNECTION` | `redis` |
| `MAIL_MAILER` | `smtp` (Resend) |
| `REDIS_HOST` | `127.0.0.1` |

#### Sicherheitsvorgaben

- DB-Passwort via `.env`-File oder Docker Secret, nie im Image
- `APP_DEBUG=false` in Produktion
- Stripe Webhook Secret konfiguriert
- Trusted-Proxies-Middleware für Reverse Proxy / TLS-Termination
- SSL/TLS via nginx (Let's Encrypt oder externe Terminierung)

---

### 12.11 Bugfix-Liste (Juli 2026)

Die folgenden drei Bugs wurden bei manuellem Testing identifiziert und müssen vor dem Go-Live behoben werden. Jeder Bug ist atomar in einem eigenen Task dokumentiert.

#### BUG-FIX-01: Passwort bereits bei der Erstellung festlegbar

**Symptom:** Der Creator zeigt unter "Advanced settings" nur den Hinweis "Password protection is managed after creation" — es gibt kein Eingabefeld. Nutzer müssen das Passwort nachträglich über den Editor setzen, was unnötige Reibung erzeugt.

**Ursache:** `QrCreator.php` enthält bereits die Property `$password`, die Validation-Regel (`nullable, string, min:4`) und die Payload-Übergabe an den Service. Die Blade-View (`qr-creator.blade.php`) rendern jedoch kein Input-Feld, sondern nur einen Platzhalter-Text.

**Lösung:** Das Password-Input-Feld in den Advanced-Settings-Bereich des Creators einfügen (analog zum Editor). Der Backend-Code ist bereits vollständig funktionsfähig — es ist ein reiner UI-Fix.

**Anforderung:** §3.1.2 listet `password` als optionalen Erstellungsparameter. Der Creator muss dieses Feld zur Verfügung stellen.

#### BUG-FIX-02: Revision-Restore muss vollständig und getestet sein

**Symptom:** Der "Restore"-Button in der Versionshistorie existiert, aber:
1. Der einzige Test (`test_restore_via_service_brings_back_previous_content`) testet direkte `$qrCode->update()`-Aufrufe, nicht die Livewire-Methode `restoreRevision()`.
2. `restoreRevision()` stellt `status` nicht wieder her, obwohl der Observer `status` in den Snapshot aufnimmt (TRACKABLE = `['title', 'content', 'settings', 'status']`).

**Lösung:**
- `restoreRevision()` so erweitern, dass `status` aus dem Snapshot wiederhergestellt wird (sofern im Snapshot vorhanden).
- Feature-Test schreiben, der `restoreRevision()` als Livewire-Call aufruft und alle vier Felder (title, content, settings, status) nach der Wiederherstellung verifiziert.
- Edge-Case: Snapshot ohne status-Feld (alte Revisionen) → Status bleibt unverändert.

#### BUG-FIX-03: Fehlerkorrektur-Level im Editor mit Bestätigung

**Symptom:** Das visuelle Design-Panel (`qr-design-panel.blade.php`) ist shared zwischen Creator und Editor. Die Fehlerkorrektur (Error Correction Level: L/M/Q/H) ist über `wire:model.live="style.error_correction"` im Editor veränderbar.

**Problem:** Eine Änderung der Fehlerkorrektur erzeugt ein visuell anderes QR-Bild. Da der QR-Code nur die Resolver-URL kodiert (z. B. `https://qrm.sg/a7f3x2`), funktioniert der alte Code weiterhin. Es entsteht aber ein neues QR-Bild, das neu heruntergeladen und gedruckt werden muss. Im Free-Tier zählt dies als neuer QR-Code gegen das Limit.

**Lösung:** Das Error-Correction-Dropdown im Editor ist aktiviert. Bei einer Änderung erscheint ein Bestätigungsdialog (amber warning box) mit Hinweis: "Changing the error correction level produces a visually different QR code. The old code still works, but you will need to download and print the new one." Für Free-Nutzer wird zusätzlich gewarnt: "On the Free plan, this counts as a new QR code against your limit." Die Änderung wird erst nach expliziter Bestätigung (`confirmEcChange`) übernommen; bis dahin bleibt der alte Wert aktiv (`cancelEcChange`).

## 13. Glossar

| Begriff | Definition |
|---|---|
| **QR-Code** | Quick Response Code — zweidimensionaler Barcode |
| **Burn after reading** | Genau ein Resolver-Zugriff wird atomar zugelassen; anschließend wird der QR-Code deaktiviert, aber nicht physisch gelöscht |
| **Hot Path** | Der performance-kritische Codepfad für QR-Auflösung |
| **Cache-Hit / Cache-Miss** | Daten im Cache gefunden / nicht gefunden |
| **Laravel Nova** | Administrationsoberfläche für interne Betriebs- und Supportaufgaben |
| **Laravel Cashier** | Laravel-Paket für Stripe-Abos und Billing-Logik |
| **Laravel Sanctum** | Laravel-Lösung für API-Tokens und Session-nahe Authentifizierung |
| **Horizon** | Laravel-Werkzeug zur Beobachtung und Steuerung von Queue-Workern |
| **vCard** | Elektronische Visitenkarte (VCF-Format) |
| **ICS** | iCalendar-Format für Kalendereinträge |
| **JSONB** | PostgreSQL-Datentyp für indexiertes JSON |
| **DSGVO** | Datenschutz-Grundverordnung der EU |
| **Freemium** | Geschäftsmodell: kostenlose Basis + kostenpflichtige Premium-Features |
| **ACID** | Atomicity, Consistency, Isolation, Durability — Datenbankgarantien |
| **GIN** | Generalized Inverted Index — PostgreSQL-Index für JSONB |
