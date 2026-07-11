#!/usr/bin/env bash
# Test: 30-day rolling retention pruning.
# Proves prune_older_than_retention() removes only files strictly older than the
# window, keeps fresh backups, and never touches non-backup files.
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export BACKUP_DB_PASSWORD=x BACKUP_ENCRYPTION_PASSPHRASE=x
. "$SCRIPT_DIR/../bin/lib/common.sh"
. "$SCRIPT_DIR/lib/test_common.sh"
export BACKUP_LOCAL_DIR BACKUP_RETENTION_DAYS=30

printf 'test_retention: 30-day rolling window\n'
new_test_dir
BACKUP_LOCAL_DIR="$TEST_DIR"

mk_backup() {  # <name> <days-ago>
  : > "$TEST_DIR/$1.dump.gz.gpg"
  touch -d "$2 days ago" "$TEST_DIR/$1.dump.gz.gpg"
}

# 40 daily backups spanning 0..39 days.
for d in $(seq 0 39); do mk_backup "qrm-day${d}" "$d"; done
# Non-backup file that is very old -> must survive pruning.
: > "$TEST_DIR/README.md"; touch -d "100 days ago" "$TEST_DIR/README.md"

before_total=$(find "$TEST_DIR" -name 'qrm-*.dump.gz.gpg' | wc -l)
assert_eq 40 "$before_total" "40 backups created before prune"

removed="$(prune_older_than_retention "$TEST_DIR" "qrm-")"

# KEY invariant: after pruning, NOTHING older than retention remains among backups.
leftover_old=$(find "$TEST_DIR" -name 'qrm-*.dump.gz.gpg' -mtime +30 | wc -l)
assert_eq 0 "$leftover_old" "no backup older than 30d remains after prune"

# Fresh backups (last 29 days) must all survive.
fresh_kept=$(find "$TEST_DIR" -name 'qrm-day*.dump.gz.gpg' -mtime -29 | wc -l)
assert_eq 30 "$fresh_kept" "all fresh backups (0..29d) kept"

# At least the clearly-old ones (31..39) were removed.
removed_min=9
[[ "$removed" -ge "$removed_min" ]] && assert_eq 1 1 "removed clearly-old backups (>=9)" \
  || assert_eq 0 1 "removed clearly-old backups (>=9, got $removed)"

# Non-backup file untouched.
assert_file_exists "$TEST_DIR/README.md" "non-backup file untouched by prune"

finish_suite "test_retention" || exit 1
