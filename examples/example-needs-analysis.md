# Bedarfsanalyse: Team Availability Board
Stack Node/TypeScript, PostgreSQL/Migration, Unit + API/DB Integration + UI-Smoke, Docker Compose/trusted Proxy/readiness.

Rollen: Planung/Decomposition A; Backend/API/DB/Frontend A; Test B–A; Security/DevOps/QA/Release A; Doku B. Subscription bevorzugt, paid_low erlaubt; Premium nur ungeklärte Auth-Grenze.

Risiken: Header Spoofing hoch -> Proxy-Allowlist/Integration/Security; XSS hoch -> Encoding/UI/API-Tests; Migration mittel -> Unique/Backup/Rollback; PII Logs mittel -> Redaction.

Budget: Planung 1,5; Implementation je Task 1,0; Security 1,5; Release 1,0 Einheiten. A/paid_standard Fallback, kein Free-Finalreview. Gate erfüllt -> `phase_planning`.
