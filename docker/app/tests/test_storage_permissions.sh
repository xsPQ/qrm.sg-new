#!/usr/bin/env bash
# shellcheck shell=bash
#
# Regression test for DEV-858: storage/framework/views (and the rest of
# storage/ + bootstrap/cache/) MUST be writable by www-data after the app
# container starts, even when a host bind-mount or a root-run process reset
# ownership to root.
#
# Designed to run INSIDE the built app image as root:
#
#   docker build -f docker/app/Dockerfile --target production -t qrm-sg:test .
#   docker run --rm -u 0:0 qrm-sg:test sh docker/app/tests/test_storage_permissions.sh
#
# Exit codes: 0 = pass, non-zero = fail.
#
# Co-Authored-By: Paperclip <noreply@paperclip.ing>
set -euo pipefail

WORKDIR="${WORKDIR:-/var/www}"
ENTRYPOINT="${ENTRYPOINT:-/usr/local/bin/docker-qrm-entrypoint.sh}"
WWW_USER="${WWW_USER:-www-data}"

PASS=0
FAIL=0
assert() { # assert <description> <condition-cmd...>
    local desc="$1"; shift
    if "$@" >/dev/null 2>&1; then
        echo "ok   - $desc"; PASS=$((PASS+1))
    else
        echo "FAIL - $desc"; FAIL=$((FAIL+1))
    fi
}

echo "# DEV-858 storage-permission regression test (as $(id -un))"

# --- Simulate the broken state the bug report described: ---------------------
# root-owned compiled views + a missing writable dir, exactly as a host
# bind-mount or a root-run worker would leave it.
mkdir -p "$WORKDIR/storage/framework/views" "$WORKDIR/bootstrap/cache"
echo '<?php // simulated root-owned compiled view' \
    > "$WORKDIR/storage/framework/views/abc123.php"
chown -R 0:0 "$WORKDIR/storage" "$WORKDIR/bootstrap/cache"
chmod -R 755 "$WORKDIR/storage" "$WORKDIR/bootstrap/cache"

# --- Run the entrypoint against a harmless command ---------------------------
# `true` exits 0; the entrypoint must chown+chmod then exec it (dropping to
# www-data for non-php-fpm commands).
"$ENTRYPOINT" /bin/true

# --- Assertions: www-data can now write everywhere it must -------------------
for d in \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache; do
    assert "dir exists: $d"            test -d "$WORKDIR/$d"
    assert "www-data writable: $d"     su-exec "$WWW_USER" test -w "$WORKDIR/$d" \
        || gosu "$WWW_USER" test -w "$WORKDIR/$d"
done

# tempnam() is the exact PHP call that failed in production; prove it works
# now from the www-data uid in the views dir.
tmpout="$(su-exec "$WWW_USER" php -r 'echo tempnam("/var/www/storage/framework/views","php");' 2>/dev/null \
          || gosu "$WWW_USER" php -r 'echo tempnam("/var/www/storage/framework/views","php");' 2>/dev/null || true)"
assert "tempnam() succeeds as www-data" test -n "$tmpout"
[ -n "$tmpout" ] && rm -f "$tmpout"

echo
echo "results: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
