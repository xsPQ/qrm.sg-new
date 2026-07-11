#!/usr/bin/env bash
# Restore a qrm.sg encrypted backup into a target database.
#
# Usage:
#   restore.sh <backup-stem|encrypted-file> [--target-db NAME]
#
# By default restores into BACKUP_DB_NAME. Provide --target-db to restore into
# a different (typically temporary) database for verification.
#
# Source: local file if present, else downloaded from BACKUP_S3_URI.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/common.sh
. "$SCRIPT_DIR/lib/common.sh"

require_cmd pg_restore gzip gpg sha256sum || exit "$E_CONFIG"
export_db_password

TARGET_DB="$BACKUP_DB_NAME"
SRC="$1"; shift || true
while (($#)); do
  case "$1" in
    --target-db) TARGET_DB="$2"; shift 2;;
    *) log_error "unknown arg: $1"; exit "$E_CONFIG";;
  esac
done

resolve_stem() {
  local s="$1"
  case "$s" in
    *.dump.gz.gpg) basename "$s";;
    *) echo "qrm-${s}.dump.gz.gpg";;
  esac
}

ENC_NAME="$(resolve_stem "$SRC")"
ENC_LOCAL="$BACKUP_LOCAL_DIR/$ENC_NAME"

WORK="$(mktemp -d)"
trap 'rc=$?; rm -rf -- "$WORK"; return $rc' EXIT

# ---- Locate encrypted backup ---------------------------------------------
if [[ -f "$ENC_LOCAL" ]]; then
  ENC="$ENC_LOCAL"
  log_info "source: local $ENC_LOCAL"
elif [[ -n "$BACKUP_S3_URI" ]]; then
  require_cmd aws || exit "$E_CONFIG"
  ENC="$WORK/$ENC_NAME"
  log_info "source: download ${BACKUP_S3_URI%/}/${ENC_NAME}"
  if ! s3_cp_down "${BACKUP_S3_URI%/}/${ENC_NAME}" "$ENC"; then
    log_error "remote download failed"; exit "$E_UPLOAD"
  fi
else
  log_error "backup not found locally and no BACKUP_S3_URI: $ENC_NAME"; exit "$E_CONFIG"
fi

[[ -f "$ENC" ]] || { log_error "encrypted backup missing: $ENC"; exit "$E_CONFIG"; }

# ---- Decrypt --------------------------------------------------------------
DEC="$WORK/decrypted.dump.gz"
if ! decrypt_file "$ENC" "$DEC"; then
  log_error "decryption failed (wrong passphrase / corrupt?)"; exit "$E_ENCRYPT"
fi

# ---- Decompress -----------------------------------------------------------
DUMP="$WORK/restore.dump"
if ! gunzip -c "$DEC" > "$DUMP"; then
  log_error "gunzip failed"; exit "$E_RESTORE"
fi
log_info "decrypted + decompressed: $(bytes_of "$DUMP") bytes"

# ---- Restore --------------------------------------------------------------
log_info "pg_restore -> ${BACKUP_DB_HOST}:${BACKUP_DB_PORT}/${TARGET_DB}"
if ! pg_restore \
    -h "$BACKUP_DB_HOST" -p "$BACKUP_DB_PORT" -U "$BACKUP_DB_USER" -d "$TARGET_DB" \
    --no-owner --no-privileges --clean --if-exists --exit-on-error \
    "$DUMP"; then
  log_error "pg_restore failed"; exit "$E_RESTORE"
fi
log_info "restore OK into ${TARGET_DB}"
exit "$E_OK"
