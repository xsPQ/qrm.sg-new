#!/bin/sh
set -e

cd /var/www/qrm.sg

echo "[entrypoint] Starting qrm.sg container..."

# Determine mode: if /var/www/qrm.sg/.dev-mark exists, we're in dev mode.
# The override file creates this via bind-mount; prod has no such file.
DEV_MODE=false
if [ -f /var/www/qrm.sg/.dev-mark ]; then
    DEV_MODE=true
    echo "[entrypoint] DEV mode detected (bind-mount active)"
fi

# Write a clean .env that inherits from Docker environment variables.
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
DB_PASSWORD=PLACEHOLDER

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

MAIL_MAILER=log
ENVEOF

# Override .env values from Docker environment variables
if [ -n "$APP_KEY" ]; then
    sed -i "s|APP_KEY=|APP_KEY=$APP_KEY|" .env
fi
if [ -n "$APP_ENV" ]; then
    sed -i "s|APP_ENV=production|APP_ENV=$APP_ENV|" .env
fi
if [ -n "$APP_DEBUG" ]; then
    sed -i "s|APP_DEBUG=false|APP_DEBUG=$APP_DEBUG|" .env
fi
if [ -n "$APP_URL" ]; then
    sed -i "s|APP_URL=http://qrm.sg|APP_URL=$APP_URL|" .env
fi
if [ -n "$DB_DATABASE" ]; then
    sed -i "s|DB_DATABASE=qrm_sg|DB_DATABASE=$DB_DATABASE|" .env
fi
if [ -n "$DB_USERNAME" ]; then
    sed -i "s|DB_USERNAME=qrm|DB_USERNAME=$DB_USERNAME|" .env
fi
if [ -n "$DB_PASSWORD" ]; then
    sed -i "s|DB_PASSWORD=PLACEHOLDER|DB_PASSWORD=$DB_PASSWORD|" .env
fi
if [ -n "$CACHE_STORE" ]; then
    sed -i "s|CACHE_STORE=file|CACHE_STORE=$CACHE_STORE|" .env
fi
if [ -n "$SESSION_DRIVER" ]; then
    sed -i "s|SESSION_DRIVER=file|SESSION_DRIVER=$SESSION_DRIVER|" .env
fi
if [ -n "$QUEUE_CONNECTION" ]; then
    sed -i "s|QUEUE_CONNECTION=database|QUEUE_CONNECTION=$QUEUE_CONNECTION|" .env
fi
if [ -n "$MAIL_MAILER" ]; then
    sed -i "s|MAIL_MAILER=log|MAIL_MAILER=$MAIL_MAILER|" .env
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

# ---------------------------------------------------------------------------
# Dependency installation (only if vendor/ is missing — dev bind-mount case)
# ---------------------------------------------------------------------------
if [ ! -d vendor ]; then
    echo "[entrypoint] Installing composer dependencies..."
    if [ "$DEV_MODE" = "true" ]; then
        composer install --no-interaction --ansi
    else
        composer install --no-dev --optimize-autoloader --no-interaction --ansi
    fi
fi

# ---------------------------------------------------------------------------
# Frontend build (only if assets are missing — dev bind-mount case)
# ---------------------------------------------------------------------------
if [ ! -f public/build/manifest.json ] || [ ! -s public/build/manifest.json ]; then
    if [ "$DEV_MODE" = "true" ] && [ -d node_modules ]; then
        echo "[entrypoint] Building frontend assets (dev)..."
        npm run build
    elif [ ! -d node_modules ]; then
        echo "[entrypoint] Installing npm dependencies + building frontend..."
        npm ci --no-audit --no-fund
        npm run build
    fi
fi

# Storage symlink
echo "[entrypoint] Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

# Ensure storage permissions
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Run migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# Cache configuration for production
if [ "$DEV_MODE" = "true" ]; then
    echo "[entrypoint] DEV mode: clearing caches for live editing..."
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
else
    echo "[entrypoint] PROD mode: caching config, routes, views..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    php artisan event:cache 2>/dev/null || true
fi

echo "[entrypoint] qrm.sg is ready. Waiting on supervisor..."
wait $SUPERVISOR_PID
