# Docker Rules

Docker nur nach Bedarfsanalyse. `docker compose` v2; minimale reproduzierbare Multi-Stage-Images; non-root wo möglich; bewusste Base-Image-Pins.

`.dockerignore` schließt `.env`, Secrets, VCS, lokale Daten, Builds und Releases aus. Keine Secrets in ARG/ENV/Layer/History. Ports, ENV, Volumes, Netzwerke und Healthchecks dokumentieren; `.env.example` nur sicher.

Verifikation: Compose config, Build, Start, Health/Smoke, Stop/Cleanup. Produktion braucht Approval/Rollback.
