#!/bin/sh
set -e

cd /var/www/qrm.sg

echo "[entrypoint] Starting qrm.sg container..."

# Install supercronic if not present (lightweight cron for containers)
if ! command -v supercronic >/dev/null 2>&1; then
    echo "[entrypoint] Installing supercronic..."
    apk add --no-cache supercronic 2>/dev/null || {
        wget -qO- "https://github.com/aptible/supercronic/releases/download/v0.2.30/supercronic-linux-amd64" > /usr/local/bin/supercronic
        chmod +x /usr/local/bin/supercronic
    }
fi

# Start supercronic scheduler via supervisor
supervisorctl start cron-scheduler 2>/dev/null || true

# Install dependencies if vendor/ is missing
if [ ! -d vendor ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Install node modules + build frontend if needed
if [ ! -d node_modules ]; then
    echo "[entrypoint] Installing npm dependencies..."
    npm ci --no-audit --no-fund
fi

if [ ! -f public/build/manifest.json ] || [ ! -s public/build/manifest.json ]; then
    echo "[entrypoint] Building frontend assets..."
    npm run build
fi

# Storage symlink
echo "[entrypoint] Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

# Run migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# Cache configuration for production
echo "[entrypoint] Caching config, routes, views..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true

# Start supervisor (nginx + php-fpm + redis + queue-worker + cron)
echo "[entrypoint] Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
