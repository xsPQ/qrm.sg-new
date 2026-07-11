#!/usr/bin/env bash
# shellcheck shell=bash
#
# Standalone smoke harness for docker/app/entrypoint.sh (DEV-858).
#
# Runs in any plain shell (no Docker, no PHP, no root required) to prove the
# entrypoint's portable logic: it creates the writable Laravel directory tree,
# recreates the public/storage symlink, and execs the passed command on the
# non-root path (the worker/scheduler code path). The root chown path is
# exercised by the in-container test test_storage_permissions.sh.
#
# Co-Authored-By: Paperclip <noreply@paperclip.ing>
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENTRYPOINT="$ROOT/entrypoint.sh"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# Fake a Laravel webroot under the temp dir.
WEBROOT="$TMP/www"
mkdir -p "$WEBROOT/public"

# Stub `php` so link_storage() can run without a real PHP runtime. It emulates
# `artisan storage:link` by creating the public/storage symlink and exits 0.
STUBBIN="$TMP/bin"
mkdir -p "$STUBBIN"
cat > "$STUBBIN/php" <<'PHP'
#!/usr/bin/env bash
# Emulate `php artisan storage:link --quiet` only; pass through exit 0.
if [ "$2" = "storage:link" ]; then
    ln -sfn ../storage/app/public "$PWD/public/storage" 2>/dev/null || true
fi
exit 0
PHP
chmod +x "$STUBBIN/php"

PASS=0; FAIL=0
assert() { local d="$1"; shift; if "$@"; then echo "ok   - $d"; PASS=$((PASS+1)); else echo "FAIL - $d"; FAIL=$((FAIL+1)); fi; }

echo "# entrypoint standalone smoke (uid=$(id -u), $ENTRYPOINT)"

# Lint first.
sh -n "$ENTRYPOINT"

# Run entrypoint in the fake webroot with a command that prints a marker.
# WORKDIR override + stubbed php on PATH. Expected stdout marker: "EXEC-OK".
OUT="$(env -i PATH="$STUBBIN:/usr/bin:/bin" WORKDIR="$WEBROOT" HOME="$TMP" \
    "$ENTRYPOINT" sh -c 'echo EXEC-OK' || true)"

assert "storage/framework/views created"   test -d "$WEBROOT/storage/framework/views"
assert "storage/framework/sessions created" test -d "$WEBROOT/storage/framework/sessions"
assert "storage/framework/cache/data created" test -d "$WEBROOT/storage/framework/cache/data"
assert "storage/logs created"              test -d "$WEBROOT/storage/logs"
assert "bootstrap/cache created"           test -d "$WEBROOT/bootstrap/cache"
assert "public/storage symlink created"    test -L "$WEBROOT/public/storage"
assert "passed command was exec'd"         sh -c "case \"$OUT\" in *EXEC-OK*) true;; *) false;; esac"

echo
echo "results: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
