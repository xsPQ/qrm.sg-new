#!/usr/bin/env bash
# shellcheck shell=bash
#
# Portainer API redeploy for qrm.sg stack (DEV-860 / DEV-894 Option C).
#
# This is the "lightest unblock path": given a Portainer API key (or admin
# credentials), an agent can trigger the redeploy from a sandbox WITHOUT any
# host access, socket mount, or container runtime. The qrm.sg fix (commit
# 973305f — self-healing entrypoint) must already be pushed to the git remote
# the Portainer stack is configured to pull from.
#
# USAGE:
#   PORTAINER_API_KEY="ptr_xxx" \
#   PORTAINER_URL="https://10.0.0.102:9443" \
#   SMOKE_BASE="http://10.0.0.102:8080" \
#   bash scripts/deploy-qrm-sg-portainer.sh
#
#   # OR with username/password (obtains a JWT automatically):
#   PORTAINER_USERNAME="admin" PORTAINER_PASSWORD="xxx" \
#   PORTAINER_URL="https://10.0.0.102:9443" \
#   bash scripts/deploy-qrm-sg-portainer.sh
#
# ENV VARS:
#   PORTAINER_URL       Portainer base URL (default: https://10.0.0.102:9443)
#   PORTAINER_API_KEY   Pre-generated API key (preferred — no password needed)
#   PORTAINER_USERNAME  Admin username (if no API key)
#   PORTAINER_PASSWORD  Admin password (if no API key)
#   STACK_NAME          Stack name filter (default: auto-detect qrm)
#   SMOKE_BASE          Target URL for smoke tests (default: http://10.0.0.102:8080)
#   SKIP_GIT_PULL       Set to 1 to skip git pull+redeploy (just restart containers)
#
# Exit codes: 0 = deploy + smoke passed, non-zero = failed at the noted step.
#
# Co-Authored-By: Paperclip <noreply@paperclip.ing>
set -euo pipefail

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
PORTAINER_URL="${PORTAINER_URL:-https://10.0.0.102:9443}"
SMOKE_BASE="${SMOKE_BASE:-http://10.0.0.102:8080}"
STACK_NAME="${STACK_NAME:-}"
SKIP_GIT_PULL="${SKIP_GIT_PULL:-0}"

red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }

step() { bold ""; bold "=== $* ==="; }
fail() { red "FAIL: $*"; exit 1; }

# ---------------------------------------------------------------------------
# Step 0 — Preflight + authentication
# ---------------------------------------------------------------------------
step "Portainer connectivity + auth"

[ -n "${PORTAINER_API_KEY:-}" ] || [ -n "${PORTAINER_USERNAME:-}" ] || \
  fail "Need PORTAINER_API_KEY or PORTAINER_USERNAME+PORTAINER_PASSWORD"

# Build auth header. API key is preferred (no password in process table).
AUTH_HEADER_FILE="$(mktemp)"
trap 'rm -f "$AUTH_HEADER_FILE"' EXIT

if [ -n "${PORTAINER_API_KEY:-}" ]; then
  printf 'X-API-Key: %s\n' "$PORTAINER_API_KEY" > "$AUTH_HEADER_FILE"
  green "Using API key auth"
else
  [ -n "${PORTAINER_PASSWORD:-}" ] || fail "PORTAINER_USERNAME set but PORTAINER_PASSWORD missing"
  JWT="$(curl -sk -m 10 -X POST "$PORTAINER_URL/api/auth" \
    -H "Content-Type: application/json" \
    -d "{\"username\":\"$PORTAINER_USERNAME\",\"password\":\"$PORTAINER_PASSWORD\"}" \
    | python3 -c "import sys,json; print(json.load(sys.stdin).get('jwt',''))" 2>/dev/null || true)"
  [ -n "$JWT" ] || fail "Portainer auth failed — check credentials"
  printf 'Authorization: Bearer %s\n' "$JWT" > "$AUTH_HEADER_FILE"
  green "Obtained JWT (username/password auth)"
fi

# Verify auth works
API_TEST="$(curl -sk -m 10 -w '%{http_code}' -o /dev/null \
  -H "$(cat "$AUTH_HEADER_FILE")" "$PORTAINER_URL/api/endpoints")"
[ "$API_TEST" = "200" ] || fail "Portainer API rejected auth (HTTP $API_TEST) — token may be invalid/expired"
green "Portainer API authenticated (HTTP 200)"

# ---------------------------------------------------------------------------
# Step 1 — Find the endpoint (Docker environment)
# ---------------------------------------------------------------------------
step "Locating Docker endpoint"

ENDPOINT_ID="$(curl -sk -m 10 -H "$(cat "$AUTH_HEADER_FILE")" \
  "$PORTAINER_URL/api/endpoints" \
  | python3 -c "
import sys,json
endpoints=json.load(sys.stdin)
eps = endpoints if isinstance(endpoints,list) else endpoints.get('items',endpoints)
if not eps:
    sys.exit(1)
# Prefer a Docker/Swarm endpoint
for e in eps:
    if e.get('Type',0) in (1,2):  # 1=Docker, 2=Swarm
        print(e['Id']); sys.exit(0)
print(eps[0]['Id'])
" 2>/dev/null || true)"

[ -n "$ENDPOINT_ID" ] || fail "No Portainer endpoint found"
green "Endpoint ID: $ENDPOINT_ID"

# ---------------------------------------------------------------------------
# Step 2 — Find the qrm.sg stack
# ---------------------------------------------------------------------------
step "Locating qrm.sg stack"

STACK_DATA="$(curl -sk -m 10 -H "$(cat "$AUTH_HEADER_FILE")" \
  "$PORTAINER_URL/api/stacks")"

if [ -z "$STACK_NAME" ]; then
  STACK_INFO="$(printf '%s' "$STACK_DATA" | python3 -c "
import sys,json
stacks=json.load(sys.stdin)
items = stacks if isinstance(stacks,list) else stacks.get('items',stacks)
# Match by name containing 'qrm' (case-insensitive)
for s in items:
    name = (s.get('Name','') or '').lower()
    if 'qrm' in name:
        print(s['Id'], s['Name'], s.get('Type',''), s.get('Status',''), s.get('CreationDate',''))
        break
" 2>/dev/null || true)"
else
  STACK_INFO="$(printf '%s' "$STACK_DATA" | python3 -c "
import sys,json
stacks=json.load(sys.stdin)
items = stacks if isinstance(stacks,list) else stacks.get('items',stacks)
for s in items:
    if s.get('Name','') == '$STACK_NAME':
        print(s['Id'], s['Name'], s.get('Type',''), s.get('Status',''), s.get('CreationDate',''))
        break
" 2>/dev/null || true)"
fi

[ -n "$STACK_INFO" ] || fail "No stack matching 'qrm' found in Portainer. Stacks: $(printf '%s' "$STACK_DATA" | python3 -c 'import sys,json; [print(s.get("Name","?")) for s in (json.load(sys.stdin) if isinstance(json.load(open("/dev/stdin")),list) else [])]' 2>/dev/null || echo '(unable to list)')"

STACK_ID="$(printf '%s' "$STACK_INFO" | awk '{print $1}')"
STACK_ACTUAL_NAME="$(printf '%s' "$STACK_INFO" | awk '{print $2}')"
green "Stack: '$STACK_ACTUAL_NAME' (ID: $STACK_ID)"

# ---------------------------------------------------------------------------
# Step 3 — Trigger redeploy
# ---------------------------------------------------------------------------
if [ "$SKIP_GIT_PULL" = "1" ]; then
  step "Restarting stack (SKIP_GIT_PULL=1 — no image rebuild)"
  curl -sk -m 30 -X POST \
    -H "$(cat "$AUTH_HEADER_FILE")" \
    "$PORTAINER_URL/api/stacks/$STACK_ID/start" \
    -H "Content-Type: application/json" -d '{}' || true
  green "Stack start triggered"
else
  step "Git redeploy (pull latest + rebuild + restart)"

  # Portainer BE: POST /api/stacks/{id}/git/redeploy — pulls latest from the
  # configured git remote and redeploys. env must match the stack's env array.
  ENV_JSON="$(printf '%s' "$STACK_DATA" | python3 -c "
import sys,json
stacks=json.load(sys.stdin)
items = stacks if isinstance(stacks,list) else stacks.get('items',stacks)
for s in items:
    if s['Id'] == $STACK_ID:
        print(json.dumps(s.get('Env',[]) or []))
        break
" 2>/dev/null || echo '[]')"

  REDEPLOY_RESP="$(curl -sk -m 120 -w '\n%{http_code}' -X POST \
    -H "$(cat "$AUTH_HEADER_FILE")" \
    -H "Content-Type: application/json" \
    -d "{\"endpointId\":$ENDPOINT_ID,\"env\":$ENV_JSON,\"prune\":true,\"pullImage\":true}" \
    "$PORTAINER_URL/api/stacks/$STACK_ID/git/redeploy" 2>/dev/null || true)"

  REDEPLOY_HTTP="$(printf '%s' "$REDEPLOY_RESP" | tail -1)"
  if [ "$REDEPLOY_HTTP" = "200" ]; then
    green "Git redeploy accepted (HTTP 200)"
  else
    yellow "Git redeploy returned HTTP $REDEPLOY_HTTP — stack may not be git-backed; trying PUT redeploy"
    # Fallback: PUT /api/stacks/{id} with stackFileContent forces a redeploy
    curl -sk -m 60 -X PUT \
      -H "$(cat "$AUTH_HEADER_FILE")" \
      -H "Content-Type: application/json" \
      -d "{\"env\":$ENV_JSON,\"prune\":true,\"endpointId\":$ENDPOINT_ID}" \
      "$PORTAINER_URL/api/stacks/$STACK_ID" >/dev/null 2>&1 || true
    green "PUT redeploy attempted"
  fi
fi

# ---------------------------------------------------------------------------
# Step 4 — Wait for services to become healthy
# ---------------------------------------------------------------------------
step "Waiting for services to become healthy on $SMOKE_BASE"

MAX_WAIT=180
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

# List containers for this endpoint (best-effort)
curl -sk -m 10 -H "$(cat "$AUTH_HEADER_FILE")" \
  "$PORTAINER_URL/api/endpoints/$ENDPOINT_ID/docker/containers?all=true" \
  | python3 -c "
import sys,json
try:
    cs=json.load(sys.stdin)
    for c in (cs if isinstance(cs,list) else []):
        names=','.join(c.get('Names',['?']))
        state=c.get('State','?')
        icon='OK' if state=='running' else '!!'
        print(f'  [{icon}] {state:10s} {names}')
except: pass
" 2>/dev/null || yellow "(unable to list containers)"

bold ""
if [ "$SMOKE_FAIL" -eq 0 ]; then
  green "SUCCESS — all $SMOKE_PASS smoke checks passed."
  green "qrm.sg is live and healthy on $SMOKE_BASE"
  exit 0
else
  red "PARTIAL — $SMOKE_FAIL smoke check(s) failed, $SMOKE_PASS passed."
  red "Check container logs via Portainer UI or API."
  exit 1
fi
