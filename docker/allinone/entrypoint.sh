#!/bin/bash
set -e

# qrm.sg All-in-One Container Entrypoint

WORKDIR="/var/www"
cd "$WORKDIR"

# ── 0. Dirs ──────────────────────────────────────────────────────────────────
mkdir -p /var/log/supervisor /var/lib/postgresql/data /var/run/postgresql /tmp/pglogs
chown -R postgres:postgres /var/lib/postgresql /var/run/postgresql /tmp/pglogs

# ── 1. Create .env ───────────────────────────────────────────────────────────
cp .env.docker .env

# ── 2. Storage permissions ──────────────────────────────────────────────────
mkdir -p storage/app/public storage/app/private \
    storage/framework/cache/data storage/framework/sessions \
    storage/framework/testing storage/framework/views storage/logs \
    bootstrap/cache
chown -R www-data:www-data /var/www
chmod -R 775 storage bootstrap/cache

# ── 3. Initialize + start PostgreSQL ─────────────────────────────────────────
PGDATA="/var/lib/postgresql/data"
PGLOG="/tmp/pglogs/postgresql.log"
if [ ! -d "$PGDATA/base" ]; then
    echo "→ Initializing PostgreSQL..."
    su postgres -c "initdb -D $PGDATA --auth=trust -U postgres"
    echo "listen_addresses='*'" >> "$PGDATA/postgresql.conf"
    echo "unix_socket_directories='/var/run/postgresql'" >> "$PGDATA/postgresql.conf"
fi

echo "→ Starting PostgreSQL..."
su postgres -c "pg_ctl -D $PGDATA -l $PGLOG start -w"

for i in $(seq 1 15); do
    if psql -U postgres -h 127.0.0.1 -c "SELECT 1" >/dev/null 2>&1; then
        echo "✓ PostgreSQL ready"
        break
    fi
    sleep 1
done

# Show PG log if not ready
if ! psql -U postgres -h 127.0.0.1 -c "SELECT 1" >/dev/null 2>&1; then
    echo "✗ PostgreSQL failed to start. Log:"
    cat "$PGLOG" 2>/dev/null || echo "(no log)"
fi

psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE qrm_sg" 2>/dev/null || true
psql -U postgres -h 127.0.0.1 -c "CREATE USER qrm_sg WITH PASSWORD 'qrm_sg' SUPERUSER" 2>/dev/null || true

# ── 4. Start Redis ───────────────────────────────────────────────────────────
echo "→ Starting Redis..."
redis-server --daemonize yes --save ""

for i in $(seq 1 10); do
    if redis-cli ping 2>/dev/null | grep -q PONG; then
        echo "✓ Redis ready"
        break
    fi
    sleep 1
done

# ── 5. Run migrations ────────────────────────────────────────────────────────
echo "→ Running migrations..."
php artisan migrate --force 2>&1

# ── 6. Seed if empty ────────────────────────────────────────────────────────
USER_COUNT=$(psql -U qrm_sg -h 127.0.0.1 -d qrm_sg -t -c "SELECT count(*) FROM users" 2>/dev/null | xargs || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "→ Seeding database..."
    php artisan db:seed --force 2>&1 || true
fi

# ── 7. Finalize ──────────────────────────────────────────────────────────────
php artisan storage:link --quiet 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "→ qrm.sg all-in-one ready on :80"

# ── 8. Stop manually-started services; supervisord will manage them ──────────
su postgres -c "pg_ctl -D $PGDATA -m fast stop" 2>/dev/null || true
redis-cli shutdown nosave 2>/dev/null || true
sleep 1

exec "$@"