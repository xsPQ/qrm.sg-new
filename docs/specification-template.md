# Pflichtenheft: [PROJECT_NAME]

> Verbindliche Quelle vor Bedarfsanalyse und Development. Eckige Felder werden durch belegte Angaben ersetzt. Unbekanntes wird als nummerierte Frage mit Owner und Frist geführt.

## 1. Dokumentinformationen

| Feld | Inhalt |
|---|---|
| Projekt | `[PROJECT_NAME]` |
| Revision/Status | `[REVISION]` / `spec_draft|spec_ready` |
| Owner/Reviewer | `[SPEC_OWNER]` / `[INDEPENDENT_REVIEWER]` |
| Datum/Quellen | `[YYYY-MM-DD]` / `[Idee, Interviews, Code, Verträge]` |

Zweck: Nachvollziehbarkeit. Qualität: Revision, Quellen und Verantwortliche belegt. Folge-Gate: Review erst nach allen Pflichtkapiteln.

## 2. Zweck und Geschäftswert

- Problem: `[konkretes Problem]`
- Nutzen: `[für wen und wodurch]`
- Erfolgsindikatoren: `[messbare Resultate]`

Qualität: Ziele sind beobachtbar und zu Anforderungen rückverfolgbar.

## 3. Systemabgrenzung

- Im System: `[Komponente/Fähigkeit]`
- Außerhalb: `[Nicht-Ziel mit Begründung]`
- Extern verantwortlich: `[System/Team -> Verantwortung]`

Zweck: Scope Creep verhindern. Folge-Gate: Jede Phase liegt innerhalb dieser Grenze.

## 4. Zielgruppen und Flows

| Nutzergruppe | Ziel | Umgebung | Rechte/Schutzbedarf |
|---|---|---|---|
| `[Gruppe]` | `[Aufgabe]` | `[Gerät/Betrieb]` | `[Rolle/Daten]` |

Primäre Flows enthalten Startbedingung, Schritte, Ergebnis und Fehlerpfad.

## 5. Zielumgebung und Betrieb

- Plattform/Runtime: `[Angabe]`
- Last/Verfügbarkeit: `[messbare Ziele]`
- Deployment/Betreiber: `[Angabe]`
- Datenstandort/Retention: `[Region, Aufbewahrung, Löschung]`

Folge-Gate: Die Bedarfsanalyse kann Stack und Infrastruktur ableiten.

## 6. Architektur

| Baustein | Verantwortung | Darf abhängen von | Darf nicht abhängen von |
|---|---|---|---|
| `[Baustein]` | `[eine Verantwortung]` | `[Abhängigkeiten]` | `[Grenzen]` |

Alternativen und Entscheidungen zusätzlich als ADR dokumentieren.

## 7. Datenmodell

| Entität | Schlüsselfelder | Invarianten | Lebenszyklus | Schutzbedarf |
|---|---|---|---|---|
| `[Entität]` | `[Felder]` | `[Regeln]` | `[Status/Retention]` | `[Klasse]` |

Migration, Löschung, Backup und Datenhoheit beschreiben. Unklare Verantwortung blockiert `spec_ready`.

## 8. Schnittstellen

| ID | Schnittstelle | Richtung | Vertrag/Auth | Timeout/Retry | Fehler/Fallback |
|---|---|---|---|---|---|
| INT-001 | `[API/Event/File]` | `[in/out]` | `[Schema/Auth]` | `[Grenzen]` | `[sichtbarer Fehler]` |

Keine Integration ohne Owner, Sicherheitsgrenze und Fehlerfall.

## 9. Funktionale Anforderungen

### FR-001: [Kurzname]

- Muss: `[Unter Bedingung X liefert das System Ergebnis Y.]`
- Quelle/Nutzen: `[Referenz]`
- Eingaben/Ergebnis: `[Daten]` / `[Output]`
- Edge Cases/Fehler: `[Grenzen]` / `[Meldung/Fallback]`
- Abnahme: `AC-001`

Qualität: stabile ID, Priorität, Quelle und prüfbare Formulierung.

## 10. Nichtfunktionale Anforderungen

| ID | Kategorie | Messbare Anforderung | Messmethode |
|---|---|---|---|
| NFR-001 | Performance | `[Ziel + Lastprofil]` | `[Test]` |
| NFR-002 | Zuverlässigkeit | `[Ziel]` | `[Nachweis]` |
| NFR-003 | Accessibility | `[Standard]` | `[Audit]` |
| NFR-004 | Wartbarkeit | `[Grenze]` | `[Analyse]` |

## 11. Sicherheit und Datenschutz

- Authentifizierung/Autorisierung: `[Mechanismen und Grenzen]`
- Eingabevalidierung: `[Vertrauensgrenzen]`
- Secrets: `[Store/Rotation]`
- Personenbezogene Daten: `[Kategorien/Policy]`
- Logging/Audit: `[Ereignisse/Redaction]`
- Bedrohungen: `[Szenario -> Kontrolle -> Test]`
- Security-Review: `[Trigger]`

Dieses Kapitel darf nicht leer sein. Kritische Lücken verhindern Development.

## 12. Observability und Recovery

- Konfiguration/`.env.example`: `[Vertrag]`
- Logs/Metriken/Healthchecks: `[Signale]`
- Backup/Restore: `[RPO/RTO/Test]`
- Rollback: `[Mechanismus/Kriterium]`

## 13. Teststrategie

| Ebene | Umfang | Befehl/Prüfung | Gate |
|---|---|---|---|
| Unit | `[Domain]` | `[Befehl]` | `[Kriterium]` |
| Integration | `[DB/API]` | `[Befehl]` | `[Kriterium]` |
| E2E/Smoke | `[kritischer Flow]` | `[Befehl]` | `[Kriterium]` |
| Security | `[negative Pfade]` | `[Audit]` | `[Kriterium]` |
| Manuell | `[begründeter Fall]` | `[Schritte]` | `[Erwartung]` |

## 14. Abnahmekriterien

### AC-001: [Ergebnis]

- Gegeben/Wenn/Dann: `[Zustand]` / `[Aktion]` / `[messbares Ergebnis]`
- Nachweis: `[Test/Artefakt]`
- Deckt ab: `FR-001`

## 15. Release und Migration

- Versionierung/Releaseeinheit: `[Regel]`
- Kompatibilität/Migration: `[Angabe]`
- Rollout/Rollback: `[Stufen und Signal]`
- Freigabe: `[Owner/Approval]`

## 16. Risiken und Fragen

| ID | Typ | Beschreibung | Auswirkung | Owner | Fällig | Blockiert? |
|---|---|---|---|---|---|---|
| Q-001 | Frage | `[Entscheidung]` | `[Folge]` | `[Owner]` | `[Datum]` | ja/nein |

## 17. Traceability

| Ziel | Anforderungen | Abnahme | Phase |
|---|---|---|---|
| `G-1` | `FR-001, NFR-001` | `AC-001` | `P1` |

## 18. Specification-Gate

- [ ] Zweck, Nutzer und Nicht-Ziele eindeutig.
- [ ] Flows, Daten und Schnittstellen beschrieben.
- [ ] Anforderungen mit IDs und prüfbaren Abnahmen.
- [ ] Sicherheit, Datenschutz, Betrieb und Recovery konkret.
- [ ] Zielumgebung und technische Grenzen klar.
- [ ] Keine blockierenden offenen Fragen.
- [ ] Traceability vollständig.
- [ ] Reviewer, Revision und Entscheidung dokumentiert.

**Nur dann:** `workflow_state = needs_analysis`.
