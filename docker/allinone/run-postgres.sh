#!/bin/bash
# Starts PostgreSQL in foreground for supervisord
PGDATA="/var/lib/postgresql/data"

# Ensure dirs
mkdir -p /var/run/postgresql /var/log
chown -R postgres:postgres /var/run/postgresql /var/lib/postgresql

# Initialize if needed (already done by entrypoint, but be safe)
if [ ! -d "$PGDATA/base" ]; then
    su postgres -c "initdb -D $PGDATA --auth=trust -U postgres"
    echo "listen_addresses='*'" >> "$PGDATA/postgresql.conf"
    echo "unix_socket_directories='/var/run/postgresql'" >> "$PGDATA/postgresql.conf"
fi

# Ensure database + user exist
su postgres -c "pg_ctl -D $PGDATA -l /var/log/pg.log start -w" 2>/dev/null
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE qrm_sg" 2>/dev/null || true
psql -U postgres -h 127.0.0.1 -c "CREATE USER qrm_sg WITH PASSWORD 'qrm_sg' SUPERUSER" 2>/dev/null || true
su postgres -c "pg_ctl -D $PGDATA -m fast stop" 2>/dev/null

# Run in foreground
exec su postgres -c "postgres -D $PGDATA -h 0.0.0.0"