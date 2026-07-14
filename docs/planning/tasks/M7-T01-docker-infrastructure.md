# DEPLOY-01: Docker Infrastruktur

## Status
❌ Nicht umgesetzt

## Priorität
Hoch (Go-Live Blocker)

## Beschreibung
Vollständige Docker-Infrastruktur für qrm.sg: ein App-Container (Nginx + PHP-FPM + Supervisor + Redis) und ein PostgreSQL-Container, gesteuert via docker-compose.

## Aufgaben

### A1: Dockerfile
- Basis: `php:8.3-fpm-alpine` + Nginx + Supervisor + Redis + Node.js + PostgreSQL Client
- PHP-Extensions: pdo_pgsql, gd, bcmath, redis, intl, zip, opcache
- Supervisor-Konfiguration für 4 Prozesse: nginx, php-fpm, queue-worker, cron-scheduler
- Redis als lokaler Dienst (nicht eigener Container)
- Composer + npm global verfügbar
- Working Dir: `/var/www/qrm.sg`

### A2: docker-compose.yml
- Service `app`: baut aus Dockerfile, Port 8080:80, Volume für Code
- Service `db`: `postgres:16-alpine`, Volume `pg-data`, Healthcheck
- Netzwerk zwischen app und db
- `.env`-File für Secrets (DB_PASSWORD etc.)

### A3: Supervisord Config
```ini
[program:nginx]
command=nginx -g "daemon off;"
[program:php-fpm]
command=php-fPM
[program:queue-worker]
command=php /var/www/qrm.sg/artisan queue:work redis --sleep=3 --tries=3
[program:redis]
command=redis-server --save ""
```

### A4: nginx config
- Document root: `/var/www/qrm.sg/public`
- PHP-FPM via fastcgi_pass
- Asset-Caching Headers
- Frontend-Routing (catch-all auf index.php)

### A5: Entrypoint Script
- `storage:link`
- `migrate --force`
- `config:cache`, `route:cache`, `view:cache`
- Starte Supervisor

### A6: .dockerignore
- vendor/, node_modules/, .git (nur für Build — Code kommt via Volume)
- .env (wird zur Laufzeit gemountet)

### A7: Production .env Template
- Alle Werte für Produktion vorbereitet

## Verifikation
- `docker compose up -d` startet beide Container
- `curl http://localhost:8080` liefert Landing Page
- `curl http://localhost:8080/login` liefert Login-Formular
- `docker compose exec app php artisan test` läuft grün
- `docker compose exec app php artisan migrate:status` zeigt alle Migrations als "Ran"
