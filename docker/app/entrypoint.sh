#!/bin/sh
# =============================================================================
# qrm.sg application container entrypoint.
#
# Fixes DEV-858: every Blade page (/login, /register, /admin, /up, ...) threw
# HTTP 500 with a tempnam() failure because storage/framework/views (and the
# rest of storage/ + bootstrap/cache/) was not writable by the www-data user
# that runs the php-fpm workers.
#
# Root cause: the Dockerfile only normalises ownership/perms at *build* time.
# At runtime a host bind-mount on storage/ (or files written by a root-run
# worker/scheduler) resets ownership to root, so www-data can no longer write
# and tempnam() fails. There was no startup self-heal.
#
# This entrypoint re-establishes writable ownership on every container start,
# regardless of how storage/ is mounted, then drops to www-data for every
# process EXCEPT php-fpm (whose master must stay root to manage its www-data
# pool). Running worker/scheduler as www-data is what stops root-owned view
# and cache files from reappearing.
#
# Designed for the production base image php:8.3-fpm-alpine (su-exec, www-data
# uid 82). Portable to Debian/Ubuntu hosts (gosu, www-data uid 33) so the same
# script can be exercised in dev/test.
# =============================================================================
set -e

WORKDIR="${WORKDIR:-/var/www}"
cd "$WORKDIR"

# All Laravel directories that must be writable at runtime.
ensure_writable_dirs() {
    mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache
}

# Resolve a privilege-dropping helper. Alpine ships su-exec; Debian/Ubuntu ship
# gosu. Fall back to plain exec when neither is present.
find_privdrop() {
    if command -v su-exec >/dev/null 2>&1; then
        printf '%s' su-exec
    elif command -v gosu >/dev/null 2>&1; then
        printf '%s' gosu
    fi
}

# Recreate the public/storage -> storage/app/public symlink relative to the
# container. Idempotent and safe to run repeatedly. The symlink ownership does
# not matter for www-data writability; only the target dir (already chowned)
# does, so this is fine to run as root.
link_storage() {
    if [ -x "$(command -v php 2>/dev/null)" ]; then
        # artisan storage:link is canonical and idempotent; --quiet keeps the
        # boot log clean and || true guards a missing APP_KEY at first boot.
        php artisan storage:link --quiet 2>/dev/null || \
            ln -sfn ../storage/app/public public/storage 2>/dev/null || true
    else
        ln -sfn ../storage/app/public public/storage 2>/dev/null || true
    fi
}

ensure_writable_dirs

if [ "$(id -u)" = "0" ]; then
    # ---------------------------------------------------------------------
    # Running as root: normalise ownership so www-data can write. This is the
    # self-heal that survives host bind-mounts and root-owned leftovers.
    # ---------------------------------------------------------------------
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX,o-rwx storage bootstrap/cache

    link_storage

    PRIVDROP="$(find_privdrop)"
    case "$1" in
        php-fpm|php-fpm*)
            # php-fpm master must stay root to spawn its www-data worker pool;
            # the workers themselves run as www-data and write as www-data.
            exec "$@"
            ;;
        *)
            # Worker / scheduler / artisan / any other command: drop to
            # www-data so they can never leave root-owned files behind.
            if [ -n "$PRIVDROP" ]; then
                exec "$PRIVDROP" www-data "$@"
            fi
            exec "$@"
            ;;
    esac
else
    # ---------------------------------------------------------------------
    # Already non-root (e.g. compose user: www-data): ownership is assumed
    # correct (the root app container normalised it, or the image is fresh).
    # Just ensure the symlink and run.
    # ---------------------------------------------------------------------
    link_storage
    exec "$@"
fi
