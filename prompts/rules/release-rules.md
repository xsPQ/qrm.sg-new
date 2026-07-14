# Release Rules

Release nur aus definiertem Scope. Pflicht: Version, unveränderliches Artefakt, grüne Gates, Doku/Changelog, Dependencies/SBOM nach Bedarf, Config, Migration, Backup/Rollback, Security und Approval.

Deployment dokumentiert Umgebung, Version/Digest, Zeitpunkt und Owner. Danach Healthcheck/Smoke/Logs. Fehler löst Stop/Rollback und `release_failed` aus; kein Retry ohne Diagnose.
