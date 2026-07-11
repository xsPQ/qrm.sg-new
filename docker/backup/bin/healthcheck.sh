#!/usr/bin/env bash
# Health check for the backup service.
# Healthy iff a successful backup exists and is newer than (retention - 1) days.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/common.sh
. "$SCRIPT_DIR/lib/common.sh"

HEALTH="${1:-$BACKUP_LOCAL_DIR/.last_success}"
warn_days=$((BACKUP_RETENTION_DAYS > 1 ? BACKUP_RETENTION_DAYS - 1 : 1))

if [[ ! -f "$HEALTH" ]]; then
  echo "UNHEALTHY: no successful backup recorded yet"
  exit 1
fi

last="$(head -n1 "$HEALTH")"
# last is an ISO-8601 UTC timestamp
last_epoch="$(date -u -d "$last" +%s 2>/dev/null || date -u -jf '%Y-%m-%dT%H:%M:%SZ' "$last" +%s 2>/dev/null || echo 0)"
now_epoch="$(date -u +%s)"
age_days=$(( (now_epoch - last_epoch) / 86400 ))

if (( age_days >= warn_days )); then
  echo "UNHEALTHY: last successful backup ${age_days}d ago (threshold ${warn_days}d) at ${last}"
  exit 1
fi
echo "OK: last successful backup ${age_days}d ago at ${last}"
