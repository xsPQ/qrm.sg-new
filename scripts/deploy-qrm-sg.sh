#!/usr/bin/env bash
# shellcheck shell=bash
#
# Turnkey deploy script for qrm.sg Docker stack (DEV-860 / DEV-858).
#
# Rebuilds the app image with the storage self-heal fix (commit 973305f),
# runs the in-container regression test, redeploys the 6-service stack,
# and smoke-verifies :8080.
#
# USAGE (run on the host that owns the qrm.sg Docker stack):
#   cd /paperclip/laravel-project    # or wherever the repo checkout lives
#   bash scripts/deploy-qrm-sg.sh
#
# PREREQS: docker + docker compose (or podman + podman-compose) on PATH.
#
# Exit codes: 0 = deploy + smoke passed, non-zero = failed at the noted step.
#
# Co-Authored-By: Paperclip <noreply@paperclip.ing>
set -euo pipefail

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
IMAGE_TAG="qrm-sg:latest"
SMOKE_BASE="${SMOKE_BASE:-http://127.0.0.1:8080}"
COMPOSE_FILE="$PROJECT_DIR/docker-compose.yml"

red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }

step() { bold ""; bold "=== $* ==="; }
fail() { red "FAIL: $*"; exit 1; }

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
step "Preflight checks"

command -v docker >/dev/null 2>&1 || fail "docker not found on PATH"
if docker compose version >/dev/null 2>&1; then
  COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker-compose"
else
  fail "docker compose (plugin or standalone) not found"
fi
green "docker: $(docker --version)"
green "compose: $($COMPOSE version 2>&1 | head -1)"

[ -f "$COMPOSE_FILE" ] || fail "docker-compose.yml not found at $COMPOSE_FILE"
[ -f "$PROJECT_DIR/docker/app/Dockerfile" ] || fail "Dockerfile not found"
[ -f "$PROJECT_DIR/.env" ] || fail ".env not found — copy .env.example and fill secrets first"

# ---------------------------------------------------------------------------
# Step 1 — Build the production image
# ---------------------------------------------------------------------------
step "Building app image ($IMAGE_TAG) from commit 973305f"

cd "$PROJECT_DIR"
docker build \
  -f docker/app/Dockerfile \
  --target production \
  -t "$IMAGE_TAG" \
  .
green "Image built: $IMAGE_TAG"

# ---------------------------------------------------------------------------
# Step 2 — In-container regression test (storage permissions)
# ---------------------------------------------------------------------------
step "Running storage-permission regression test"

docker run --rm -u 0:0 "$IMAGE_TAG" \
  sh docker/app/tests/test_storage_permissions.sh \
  && green "Regression test PASSED" \
  || fail "Storage-permission regression test FAILED — aborting deploy"

# ---------------------------------------------------------------------------
# Step 3 — Deploy the stack
# ---------------------------------------------------------------------------
step "Redeploying 6-service stack (preserve DB; reseed webroot only)"

# CRITICAL (DEV-860): do NOT use `down -v`. The stack declares THREE named
# volumes — webroot, db-data (PostgreSQL), redis-data (Redis). `down -v`
# removes ALL of them and would WIPE the production database and cache.
# Instead: stop containers (volumes preserved), remove ONLY the webroot
# volume so the rebuilt image re-seeds /var/www with new code + correct
# www-data ownership, then bring the stack back up. The entrypoint
# (docker/app/entrypoint.sh) re-chowns storage/ + bootstrap/cache to
# www-data on every start, so even if webroot removal is skipped the app
# self-heals.
$COMPOSE down

# Remove ONLY webroot. Resolve by the compose volume label so we never
# guess the project prefix; fall back to <project>_webroot if needed.
WEBROOT_VOLS="$(docker volume ls \
  --filter 'label=com.docker.compose.volume=webroot' -q 2>/dev/null || true)"
if [ -n "$WEBROOT_VOLS" ]; then
  yellow "Reseeding webroot (removing: $WEBROOT_VOLS); db-data + redis-data preserved"
  docker volume rm $WEBROOT_VOLS >/dev/null 2>&1 || true
else
  PROJECT_NAME="${COMPOSE_PROJECT_NAME:-$(basename "$PROJECT_DIR")}"
  PROJECT_NAME="$(printf '%s' "$PROJECT_NAME" | tr '[:upper:]' '[:lower:]')"
  if docker volume rm "${PROJECT_NAME}_webroot" >/dev/null 2>&1; then
    yellow "Reseeding webroot (${PROJECT_NAME}_webroot); db-data + redis-data preserved"
  else
    yellow "No pre-existing webroot volume (fresh deploy)"
  fi
fi

$COMPOSE up -d --build
green "Stack is up (production volumes db-data + redis-data preserved)"

# ---------------------------------------------------------------------------
# Step 4 — Wait for services to be healthy
# ---------------------------------------------------------------------------
step "Waiting for services to become healthy"

MAX_WAIT=120
ELAPSED=0
while [ "$ELAPSED" -lt "$MAX_WAIT" ]; do
  HTTP_CODE="$(curl -sS -m 5 -o /dev/null -w '%{http_code}' "$SMOKE_BASE/up" 2>/dev/null || echo "000")"
  if [ "$HTTP_CODE" != "000" ] && [ "$HTTP_CODE" != "500" ]; then
    green "App responding (HTTP $HTTP_CODE) after ${ELAPSED}s"
    break
  fi
  printf '.'
  sleep 5
  ELAPSED=$((ELAPSED + 5))
done
[ "$ELAPSED" -lt "$MAX_WAIT" ] || fail "App did not become healthy within ${MAX_WAIT}s"

# ---------------------------------------------------------------------------
# Step 5 — Smoke-verify all routes
# ---------------------------------------------------------------------------
step "Smoke-verify routes on $SMOKE_BASE"

SMOKE_PASS=0
SMOKE_FAIL=0

smoke() {
  local path="$1" expect="$2"
  local code
  code="$(curl -sS -m 10 -o /dev/null -w '%{http_code}' "$SMOKE_BASE$path" 2>/dev/null || echo "000")"
  if [ "$code" = "$expect" ]; then
    green "  ok   $path -> $code (expected $expect)"
    SMOKE_PASS=$((SMOKE_PASS + 1))
  else
    red "  FAIL $path -> $code (expected $expect)"
    SMOKE_FAIL=$((SMOKE_FAIL + 1))
  fi
}

smoke /up        200
smoke /login     200
smoke /register  200
smoke /admin     302

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
step "Deploy summary"

$COMPOSE ps

bold ""
if [ "$SMOKE_FAIL" -eq 0 ]; then
  green "SUCCESS — all $SMOKE_PASS smoke checks passed."
  green "qrm.sg is live and healthy on $SMOKE_BASE"
  exit 0
else
  red "PARTIAL — $SMOKE_FAIL smoke check(s) failed, $SMOKE_PASS passed."
  red "Check container logs: $COMPOSE logs app"
  exit 1
fi
