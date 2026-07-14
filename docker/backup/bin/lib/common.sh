#!/usr/bin/env bash
# Shared helpers for the qrm.sg backup/restore pipeline.
# Sourced by backup.sh, restore.sh, restore-test.sh, healthcheck.sh.
# Kept side-effect free at source time: only function definitions + config read.

set -o pipefail

# ---------------------------------------------------------------------------
# Configuration (read from environment). No secrets are ever persisted to disk
# or logged. The passphrase MUST be injected at runtime via env / secret store.
# ---------------------------------------------------------------------------
: "${BACKUP_DB_HOST:=postgres}"
: "${BACKUP_DB_PORT:=5432}"
: "${BACKUP_DB_NAME:=qrm}"
: "${BACKUP_DB_USER:=qrm}"
: "${BACKUP_DB_PASSWORD:?BACKUP_DB_PASSWORD is required}"

: "${BACKUP_ENCRYPTION_PASSPHRASE:?BACKUP_ENCRYPTION_PASSPHRASE is required}"

# Rolling retention in days (spec: 30).
: "${BACKUP_RETENTION_DAYS:=30}"

# Local working / archive directory inside the container.
: "${BACKUP_LOCAL_DIR:=/backups}"

# S3-compatible remote target. Empty => remote copy is skipped (local-only mode).
: "${BACKUP_S3_URI:=}"            # e.g. s3://qrm-backups/qrm-sg/daily
: "${BACKUP_S3_ENDPOINT:=}"       # S3-compatible endpoint (MinIO, Wasabi, R2, ...)
: "${BACKUP_S3_REGION:=us-east-1}"
: "${BACKUP_S3_ACCESS_KEY:=}"
: "${BACKUP_S3_SECRET_KEY:=}"

# Cron schedule (supercronic format). Defaults: daily 02:00, weekly restore test.
: "${BACKUP_SCHEDULE:=0 2 * * *}"
: "${BACKUP_RESTORE_TEST_SCHEDULE:=0 3 * * 0}"

# Consistency-checked tables (core business data). Used by restore-test.sh.
BACKUP_CHECK_TABLES=(users qr_codes qr_code_routes scans scan_stats_hourly scan_stats_daily subscriptions subscription_items admin_action_logs stripe_webhook_events)

# Logging
BACKUP_LOG_LEVEL="${BACKUP_LOG_LEVEL:-INFO}"
BACKUP_TZ="${BACKUP_TZ:-Europe/Berlin}"

# ---------------------------------------------------------------------------
# Logging helpers
# ---------------------------------------------------------------------------
_log() {
  local level="$1"; shift
  local ts
  ts="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
  printf '%s [%s] %s\n' "$ts" "$level" "$*" >&2
}
log_debug() { [[ "$BACKUP_LOG_LEVEL" == "DEBUG" ]] && _log DEBUG "$@" || true; }
log_info()  { _log INFO  "$@"; }
log_warn()  { _log WARN  "$@"; }
log_error() { _log ERROR "$@"; }

# Exit codes
E_OK=0; E_CONFIG=2; E_DUMP=3; E_ENCRYPT=4; E_UPLOAD=5; E_PRUNE=6; E_RESTORE=7; E_VERIFY=8

# ---------------------------------------------------------------------------
# Timestamp helpers (UTC for filenames => deterministic ordering)
# ---------------------------------------------------------------------------
backup_timestamp() { date -u +"%Y%m%dT%H%M%SZ"; }
backup_datestamp() { date -u +"%Y-%m-%d"; }

# ---------------------------------------------------------------------------
# pg_dump connection arguments. Password injected via PGPASSWORD (never on CLI).
# ---------------------------------------------------------------------------
pg_conn_args() {
  printf -- '-h %s -p %s -U %s -d %s' "$BACKUP_DB_HOST" "$BACKUP_DB_PORT" "$BACKUP_DB_USER" "$BACKUP_DB_NAME"
}

# Export PGPASSWORD for child processes. Caller responsibility.
export_db_password() {
  export PGPASSWORD="$BACKUP_DB_PASSWORD"
}

# ---------------------------------------------------------------------------
# GPG symmetric encryption helpers (AES256). Production crypto path.
# Passphrase read from env, never written to disk, never echoed.
# ---------------------------------------------------------------------------
encrypt_file() {
  # $1 = plaintext input path, $2 = ciphertext output path
  gpg --batch --yes --quiet --no-tty --pinentry-mode loopback \
    --passphrase-fd 3 \
    --symmetric --cipher-algo AES256 --compress-algo none \
    --output "$2" "$1" 3<<<"$BACKUP_ENCRYPTION_PASSPHRASE"
}

decrypt_file() {
  # $1 = ciphertext input path, $2 = plaintext output path
  gpg --batch --yes --quiet --no-tty --pinentry-mode loopback \
    --passphrase-fd 3 --decrypt \
    --output "$2" "$1" 3<<<"$BACKUP_ENCRYPTION_PASSPHRASE"
}

# ---------------------------------------------------------------------------
# Integrity helpers
# ---------------------------------------------------------------------------
sha256_of() { sha256sum "$1" | awk '{print $1}'; }
bytes_of()  { stat -c '%s' "$1" 2>/dev/null || stat -f '%z' "$1"; }

# ---------------------------------------------------------------------------
# Retention: delete files matching <stem>*.gpg older than $BACKUP_RETENTION_DAYS
# under $1. Operates on mtime. Returns count of deleted files on stdout.
# ---------------------------------------------------------------------------
prune_older_than_retention() {
  local dir="$1"; local stem="${2:-}"
  local pattern="${stem:+$stem*}.gpg"
  [[ -z "$stem" ]] && pattern='*.gpg'
  local deleted=0 f
  while IFS= read -r f; do
    [[ -z "$f" ]] && continue
    if rm -f -- "$f"; then
      deleted=$((deleted + 1))
      log_info "retention: removed $(basename "$f")"
    fi
  done < <(find "$dir" -maxdepth 1 -type f -name "$pattern" -mtime "+$BACKUP_RETENTION_DAYS" 2>/dev/null)
  echo "$deleted"
}

# ---------------------------------------------------------------------------
# S3 helpers (AWS CLI v2). Endpoint + path-style for S3-compatible stores.
# ---------------------------------------------------------------------------
s3_env() {
  [[ -z "$BACKUP_S3_URI" ]] && return 1
  export AWS_ACCESS_KEY_ID="$BACKUP_S3_ACCESS_KEY"
  export AWS_SECRET_ACCESS_KEY="$BACKUP_S3_SECRET_KEY"
  export AWS_DEFAULT_REGION="$BACKUP_S3_REGION"
  return 0
}

s3_cp_up() {
  # $1 = local file, $2 = s3 uri
  local args=(--no-progress)
  [[ -n "$BACKUP_S3_ENDPOINT" ]] && args+=(--endpoint-url "$BACKUP_S3_ENDPOINT")
  args+=("$1" "$2")
  s3_env && aws s3 cp "${args[@]}"
}

s3_cp_down() {
  # $1 = s3 uri, $2 = local file
  local args=(--no-progress)
  [[ -n "$BACKUP_S3_ENDPOINT" ]] && args+=(--endpoint-url "$BACKUP_S3_ENDPOINT")
  args+=("$1" "$2")
  s3_env && aws s3 cp "${args[@]}"
}

s3_ls() {
  # $1 = s3 uri prefix
  local args=(--recursive)
  [[ -n "$BACKUP_S3_ENDPOINT" ]] && args+=(--endpoint-url "$BACKUP_S3_ENDPOINT")
  s3_env && aws s3 ls "${args[@]}" "$1"
}

s3_rm() {
  # $1 = s3 uri
  local args=()
  [[ -n "$BACKUP_S3_ENDPOINT" ]] && args+=(--endpoint-url "$BACKUP_S3_ENDPOINT")
  s3_env && aws s3 rm "$1" "${args[@]}"
}

# Prune remote: delete objects older than retention under BACKUP_S3_URI.
prune_remote() {
  [[ -z "$BACKUP_S3_URI" ]] && return 0
  local cutoff ts obj deleted=0
  cutoff=$(date -u -d "-${BACKUP_RETENTION_DAYS} days" +"%Y-%m-%dT%H:%M:%S" 2>/dev/null) || return 0
  while IFS= read -r line; do
    # aws s3 ls output: "2026-06-01 03:00:00  1234  qrm-...gpg"
    ts="$(echo "$line" | awk '{print $1"T"$2"Z"}')"
    obj="$(echo "$line" | awk '{print $4}')"
    [[ -z "$obj" ]] && continue
    if [[ "$ts" < "$cutoff" ]]; then
      if s3_rm "${BACKUP_S3_URI%/}/${obj}"; then
        deleted=$((deleted + 1))
        log_info "retention(remote): removed $obj"
      fi
    fi
  done < <(s3_ls "$BACKUP_S3_URI")
  echo "$deleted"
}

# ---------------------------------------------------------------------------
# Required-binary guard
# ---------------------------------------------------------------------------
require_cmd() {
  local missing=() c
  for c in "$@"; do command -v "$c" >/dev/null 2>&1 || missing+=("$c"); done
  if ((${#missing[@]})); then
    log_error "missing required commands: ${missing[*]}"
    return 1
  fi
  return 0
}
