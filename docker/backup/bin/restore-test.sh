#!/usr/bin/env bash
# Automated restore test for qrm.sg backups.
#
# Workflow:
#   1. Snapshot row-counts of core business tables from the LIVE database.
#   2. Create a throwaway database (qrm_restoretest_<ts>).
#   3. Restore the latest encrypted backup into it.
#   4. Snapshot row-counts from the restored temp database.
#   5. Compare counts (source vs restored). PASS only on exact match.
#   6. Write a structured restore-test log + JSON result.
#   7. Drop the temp database (always, even on failure).
#
# This satisfies Pflichtenheft §7.2 "Wiederherstellung getestet".
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/common.sh
. "$SCRIPT_DIR/lib/common.sh"

require_cmd psql pg_restore gunzip gpg jq || exit "$E_CONFIG"
export_db_password

RUN_TS="$(backup_timestamp)"
LOG_DIR="${BACKUP_RESTORE_TEST_LOG_DIR:-$BACKUP_LOCAL_DIR/restore-tests}"
mkdir -p "$LOG_DIR"
LOG="$LOG_DIR/restore-test-${RUN_TS}.log"
RESULT_JSON="$LOG_DIR/restore-test-${RUN_TS}.json"
WORK="$(mktemp -d)"
trap 'rc=$?; finish_test "$rc"; rm -rf -- "$WORK"; exit "$rc"' EXIT

log_file() { printf '%s\n' "$*" | tee -a "$LOG" >&2; }
log_file "=== qrm.sg restore-test @ ${RUN_TS} ==="

TEMP_DB="qrm_restoretest_$(date -u +%s)"
ADMIN_DB="${BACKUP_DB_ADMIN_DB:-postgres}"

# ---- Pick latest encrypted backup ----------------------------------------
LATEST_ENC=""
if [[ -d "$BACKUP_LOCAL_DIR" ]]; then
  LATEST_ENC="$(ls -1t "$BACKUP_LOCAL_DIR"/qrm-*.dump.gz.gpg 2>/dev/null | head -n1 || true)"
fi
if [[ -z "$LATEST_ENC" ]] && [[ -n "$BACKUP_S3_URI" ]]; then
  require_cmd aws || exit "$E_CONFIG"
  remote_newest="$(s3_ls "$BACKUP_S3_URI" | awk '$4 ~ /\.gpg$/ {print $1" "$2" "$4}' \
    | sort -r | head -n1)"
  obj="$(echo "$remote_newest" | awk '{print $3}')"
  if [[ -n "$obj" ]]; then
    LATEST_ENC="$WORK/$obj"
    s3_cp_down "${BACKUP_S3_URI%/}/${obj}" "$LATEST_ENC" || LATEST_ENC=""
  fi
fi
if [[ -z "$LATEST_ENC" ]] || [[ ! -f "$LATEST_ENC" ]]; then
  log_file "FAIL: no encrypted backup available to test"
  exit "$E_CONFIG"
fi
log_file "subject: $(basename "$LATEST_ENC")"

# ---- 1. Source row-counts (live DB) --------------------------------------
count_tables_sql() {
  local db="$1"; local out="$2"
  : > "$out"
  for t in "${BACKUP_CHECK_TABLES[@]}"; do
    cnt="$(psql -h "$BACKUP_DB_HOST" -p "$BACKUP_DB_PORT" -U "$BACKUP_DB_USER" \
      -d "$db" -tAc "SELECT COUNT(*) FROM \"$t\";" 2>/dev/null || echo "ERR")"
    printf '%s\t%s\n' "$t" "$cnt" >> "$out"
  done
}

SRC_COUNTS="$WORK/source_counts.tsv"
log_file "snapshotting source counts from ${BACKUP_DB_NAME}..."
count_tables_sql "$BACKUP_DB_NAME" "$SRC_COUNTS"

# ---- 2. Create temp database ---------------------------------------------
log_file "creating temp database: ${TEMP_DB}"
psql -h "$BACKUP_DB_HOST" -p "$BACKUP_DB_PORT" -U "$BACKUP_DB_USER" -d "$ADMIN_DB" \
  -v ON_ERROR_STOP=1 -c "CREATE DATABASE \"$TEMP_DB\";" \
  | tee -a "$LOG" >&2 || { log_file "FAIL: could not create temp DB"; exit "$E_RESTORE"; }

# ---- 3. Decrypt + restore into temp DB -----------------------------------
DEC="$WORK/decrypted.dump.gz"
DUMP="$WORK/restore.dump"
if ! decrypt_file "$LATEST_ENC" "$DEC"; then
  log_file "FAIL: decryption failed"; exit "$E_ENCRYPT"
fi
gunzip -c "$DEC" > "$DUMP"

log_file "pg_restore into ${TEMP_DB}..."
if ! pg_restore \
    -h "$BACKUP_DB_HOST" -p "$BACKUP_DB_PORT" -U "$BACKUP_DB_USER" -d "$TEMP_DB" \
    --no-owner --no-privileges --exit-on-error --schema=public \
    "$DUMP" 2>>"$LOG"; then
  log_file "FAIL: pg_restore into temp DB failed"; exit "$E_RESTORE"
fi

# ---- 4. Restored row-counts ----------------------------------------------
REST_COUNTS="$WORK/restored_counts.tsv"
log_file "snapshotting restored counts from ${TEMP_DB}..."
count_tables_sql "$TEMP_DB" "$REST_COUNTS"

# ---- 5. Compare -----------------------------------------------------------
MISMATCH=0
COMPARISON="$WORK/comparison.tsv"
: > "$COMPARISON"
log_file "" ; log_file "table                   source   restored  status"
while IFS=$'\t' read -r t src; do
  rest="$(grep -P "^${t}\t" "$REST_COUNTS" 2>/dev/null | cut -f2 || echo "MISSING")"
  if [[ "$src" == "$rest" ]] && [[ "$src" != "ERR" ]]; then
    st="OK"; else st="MISMATCH"; MISMATCH=$((MISMATCH+1)); fi
  printf '%-24s%-9s%-10s%s\n' "$t" "$src" "$rest" "$st" >> "$COMPARISON"
  tee -a "$LOG" < <(printf '%-24s%-9s%-10s%s\n' "$t" "$src" "$rest" "$st") >&2
done < "$SRC_COUNTS"

# ---- 6. Result ------------------------------------------------------------
finish_test() {
  local rc="$1"
  local status
  if [[ "$rc" -eq 0 ]] && [[ "$MISMATCH" -eq 0 ]]; then status="PASS"; else status="FAIL"; fi
  jq -n \
    --arg ts "$RUN_TS" \
    --arg subject "$(basename "$LATEST_ENC")" \
    --arg status "$status" \
    --arg tempdb "$TEMP_DB" \
    --arg mismatches "$MISMATCH" \
    --arg log "$(basename "$LOG")" \
    '{timestamp:$ts, subject:$subject, status:$status,
      temp_database:$tempdb, mismatches:($mismatches|tonumber),
      log:$log}' > "$RESULT_JSON"

  log_file "" ; log_file "RESULT: ${status} (mismatches: ${MISMATCH})"

  # ---- 7. Drop temp database (always) -----------------------------------
  if [[ -n "${TEMP_DB:-}" ]]; then
    psql -h "$BACKUP_DB_HOST" -p "$BACKUP_DB_PORT" -U "$BACKUP_DB_USER" -d "$ADMIN_DB" \
      -c "DROP DATABASE IF EXISTS \"$TEMP_DB\";" >>"$LOG" 2>&1 || \
      log_file "WARN: could not drop temp DB $TEMP_DB (cleanup manually)"
  fi
}

if [[ "$MISMATCH" -eq 0 ]]; then
  log_file "restore-test PASS"
  exit "$E_OK"
else
  log_file "restore-test FAIL: ${MISMATCH} table count mismatch(es)"
  exit "$E_VERIFY"
fi
