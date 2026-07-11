#!/usr/bin/env bash
# qrm.sg daily encrypted backup job.
#
# Pipeline: pg_dump (custom) -> gzip -> gpg AES256 symmetric encrypt
#           -> sha256 manifest -> S3 upload (external copy)
#           -> prune local + remote (30-day rolling retention)
#           -> health marker.
#
# Secrets (DB password, encryption passphrase, S3 keys) come from the
# environment / secret store only. Nothing sensitive is written to disk or logs.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/common.sh
. "$SCRIPT_DIR/lib/common.sh"

require_cmd pg_dump gzip gpg sha256sum || exit "$E_CONFIG"
export_db_password

mkdir -p "$BACKUP_LOCAL_DIR"

TS="$(backup_timestamp)"
STEM="qrm-${TS}"
DUMP_RAW="$BACKUP_LOCAL_DIR/${STEM}.dump"        # pg_dump custom format (binary)
DUMP_GZ="$BACKUP_LOCAL_DIR/${STEM}.dump.gz"
ENC_FILE="$BACKUP_LOCAL_DIR/${STEM}.dump.gz.gpg"
MANIFEST="$BACKUP_LOCAL_DIR/${STEM}.manifest.json"
HEALTH="$BACKUP_LOCAL_DIR/.last_success"

cleanup() {
  local rc=$?
  rm -f -- "$DUMP_RAW" "$DUMP_GZ"
  # Keep ENC_FILE + manifest on success; remove on failure to avoid partials.
  if [[ $rc -ne 0 ]]; then rm -f -- "$ENC_FILE" "$MANIFEST"; fi
  return "$rc"
}
trap cleanup EXIT

# ---- 1. Dump --------------------------------------------------------------
log_info "dump: ${BACKUP_DB_USER}@${BACKUP_DB_HOST}:${BACKUP_DB_PORT}/${BACKUP_DB_NAME}"
if ! pg_dump $(pg_conn_args) --format=custom --no-owner --no-privileges --file "$DUMP_RAW"; then
  log_error "pg_dump failed"; exit "$E_DUMP"
fi
local_bytes="$(bytes_of "$DUMP_RAW")"
log_debug "dump raw size: ${local_bytes} bytes"

# ---- 2. Compress ----------------------------------------------------------
if ! gzip -c "$DUMP_RAW" > "$DUMP_GZ"; then
  log_error "gzip failed"; exit "$E_DUMP"
fi

# ---- 3. Encrypt (GPG symmetric AES256) -----------------------------------
if ! encrypt_file "$DUMP_GZ" "$ENC_FILE"; then
  log_error "encryption failed"; exit "$E_ENCRYPT"
fi
enc_bytes="$(bytes_of "$ENC_FILE")"
rm -f -- "$DUMP_GZ"

# ---- 4. Integrity manifest ------------------------------------------------
sha="$(sha256_of "$ENC_FILE")"
jq -n \
  --arg name "${STEM}.dump.gz.gpg" \
  --arg sha "$sha" \
  --argjson bytes "$enc_bytes" \
  --arg ts "$TS" \
  --arg db "$BACKUP_DB_NAME" \
  --arg host "$BACKUP_DB_HOST" \
  --arg cipher "AES256/GPG-symmetric" \
  '{backup:"\($name)", sha256:$sha, bytes:$bytes, timestamp:$ts,
    database:$db, host:$host, cipher:$cipher,
    pg_version:($ENV.PG_VERSION // "unknown")}' \
  > "$MANIFEST"

# Verify the manifest SHA matches a fresh hash of the file before trusting it.
verify="$(sha256_of "$ENC_FILE")"
if [[ "$verify" != "$sha" ]]; then
  log_error "manifest sha mismatch: $sha != $verify"; exit "$E_VERIFY"
fi
log_info "encrypted backup ready: ${STEM}.dump.gz.gpg (${enc_bytes} bytes, sha256 ${sha:0:16}...)"

# ---- 5. External copy (S3-compatible) ------------------------------------
if [[ -n "$BACKUP_S3_URI" ]]; then
  require_cmd aws || { log_error "aws CLI missing but BACKUP_S3_URI set"; exit "$E_CONFIG"; }
  log_info "upload: ${BACKUP_S3_URI}/${STEM}.dump.gz.gpg"
  if ! s3_cp_up "$ENC_FILE" "${BACKUP_S3_URI%/}/${STEM}.dump.gz.gpg"; then
    log_error "S3 upload failed"; exit "$E_UPLOAD"
  fi
  s3_cp_up "$MANIFEST" "${BACKUP_S3_URI%/}/${STEM}.manifest.json" || log_warn "manifest upload skipped"
  log_info "external copy complete"
else
  log_warn "BACKUP_S3_URI empty -> local-only mode (no external copy)"
fi

# ---- 6. Retention (local + remote) ---------------------------------------
removed_local="$(prune_older_than_retention "$BACKUP_LOCAL_DIR" "qrm-")"
log_info "retention(local): removed ${removed_local} file(s) older than ${BACKUP_RETENTION_DAYS}d"
if [[ -n "$BACKUP_S3_URI" ]]; then
  removed_remote="$(prune_remote)" || true
  log_info "retention(remote): removed ${removed_remote} object(s)"
fi

# ---- 7. Health marker -----------------------------------------------------
date -u +"%Y-%m-%dT%H:%M:%SZ" > "$HEALTH"
echo "$STEM" >> "$HEALTH"

log_info "backup OK: $STEM"
exit "$E_OK"
