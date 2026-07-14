#!/bin/sh
set -e

cd /var/www/qrm.sg

echo "[entrypoint] Starting qrm.sg container..."

# If the bind-mount .env exists, back it up and write a minimal one that
# relies on Docker environment variables for everything else.
if [ -f .env ] && [ ! -f .env.docker-backup ]; then
    cp .env .env.docker-backup
fi

# Write a clean .env that inherits from Docker environment
cat > .env << 'ENVEOF'
APP_NAME=qrm.sg
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=http://qrm.sg

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=qrm_sg
DB_USERNAME=qrm
DB_PASSWORD=${DB_PASSWORD}

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

MAIL_MAILER=log
ENVEOF

# Use the APP_KEY from Docker environment if provided
if [ -n "$APP_KEY" ]; then
    sed -i "s|APP_KEY=|APP_KEY=$APP_KEY|" .env
fi

# Start supervisor (nginx + php-fpm + redis + queue-worker + cron) FIRST
# so that Redis is available when migrations run.
echo "[entrypoint] Starting supervisor (background)..."
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf &
SUPERVISOR_PID=$!

# Wait for Redis to be ready (max 15 seconds)
echo "[entrypoint] Waiting for Redis..."
for i in $(seq 1 15); do
    if redis-cli ping 2>/dev/null | grep -q PONG; then
        echo "[entrypoint] Redis is ready."
        break
    fi
    sleep 1
done

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

echo "[entrypoint] qrm.sg is ready. Waiting on supervisor..."
wait $SUPERVISOR_PID
