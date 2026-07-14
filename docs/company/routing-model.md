# Routing Model

Routing ordnet zuerst eine Rolle, danach eine Ressource zu. Eingaben: Tasktyp, Scope, Risiko, Komplexität, Kontext, Skills, Tools, Abhängigkeiten, Verifikation, Attempt-Historie, Budget und Verfügbarkeit.

1. Task-Review muss `pass` sein.
2. Primärrolle anhand des fachlichen Änderungszentrums wählen.
3. Reviewer/Testrollen bei Bedarf ergänzen.
4. Mindesttier und zulässige Klassen setzen.
5. Register filtern; günstigste ausreichend zuverlässige Ressource wählen.
6. Maximalbudget, Timeout, Fallback und Premium-Begründung festhalten.
7. Confidence 0–1 dokumentieren; unter 0,70 zurück zur Zerlegung.

| Zentrum | Primärrolle | Tier normal | Security-Trigger |
|---|---|---|---|
| Spec/Architektur | Spec Architect | A–S | Daten/Trust Boundary |
| Planung | Phase Planner/Decomposer | A–S | Migration/Release |
| Domain | Backend Domain Developer | A–S | Auth/Geld/Lizenz/Mandant |
| API | API Developer | A | Auth/öffentlicher Vertrag |
| Daten | Database Developer | B–A; S bei Verlust | Migration/PII |
| UI | Frontend UI Developer | B–A | XSS/sensible Eingabe |
| Integration | Integration Developer | A–S | externe Daten/Secrets |
| Test/Doku | Test/Documentation Agent | B–A | Security Review separat |
| Docker/Release | DevOps/Release | A–S | Produktion |

Providerfehler nutzt Fallback. Erster Implementierungsfehler: gleicher Agent nach Diagnose. Zweiter: Debugging oder höheres Tier. Unklarer Task: Decomposer. Architekturfehler: Spec Review. Dritter Fehler: `split_required|blocked_human`.
