# Project Docker Rules

Docker-Einsatz: `[not used|development|test|release|production]`. Dateien/Services/Ports/Volumes: `[reale Angaben]`.

Pflicht: kleine reproduzierbare Images, `.dockerignore`, keine Secrets im Kontext/Image/History, non-root wo möglich, gepinnte Baseline, getrennte Dev-/Release-Concerns, dokumentierte ENV in `.env.example`, Healthcheck für langlebige Dienste.

Verifikation: `docker compose config`, Build, Start, Health/Smoke und Stop/Cleanup. Produktion benötigt Approval und Rollbackplan.
