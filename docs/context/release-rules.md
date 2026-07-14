# Project Release Rules

Versionierung/Artefakt/Registry/Umgebungen: `[reale Angaben]`.

Kein Release ohne Scope-Liste, grüne Gates, aktualisierte Doku/Changelog, Migrations- und Rollbackplan, relevante Security-Freigabe, unveränderliches Artefakt und Approval nach Policy.

Nach Deployment: Readiness/Liveness, kritischer Smoke Flow, Logs/Metriken und Versionsnachweis. Bei Fehlkriterium stoppen/rollbacken und `release_failed` mit Owner/Evidenz setzen.
