# Pflichtenheft: Team Availability Board

## Zweck/Scope
Internes Webboard zeigt für max. 50 Teammitglieder `available|focus|away` und optionale Rückkehrzeit. Nutzer ändern nur sich selbst; Firmenmitglieder lesen. Nicht enthalten: Chat, Kalender, Historie, Externe.

## Architektur/Daten
Responsive Browser-App, Node.js/TypeScript, REST, PostgreSQL; UI -> API -> Status Service -> Repository. Docker Compose intern. `User(id, displayName, active)`; `Availability(userId, state, returnAt, note, updatedAt)`. Ein aktueller Datensatz/User; Note max. 120; returnAt nur away/UTC. Auth über trusted Proxy Header.

## Anforderungen
- FR-001: aktive Mitglieder alphabetisch; leere Liste gültig.
- FR-002: nur eigener Status; invalid 400, anderer User 403.
- FR-003: away mit optionaler zukünftiger Rückkehr; Vergangenheit 400.
- FR-004: Note trimmen, HTML escapen, >120 ablehnen.
- NFR-001: p95 GET <300 ms bei 50 User/10 req/s.
- NFR-002: keine Note/Auth-Header in Logs.

## Abnahme
AC-001 drei Nutzer -> drei sortierte Einträge. AC-002 Alice PATCH Bob -> 403/keine Änderung. AC-003 valider PATCH danach sichtbar. AC-004 `<script>` wird Text.

Security Review wegen Auth-Header/XSS. Health `/health/ready` prüft DB. Migration mit Backup/Rollback. Status `spec_ready`, keine Blocker.
