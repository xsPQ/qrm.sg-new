#!/usr/bin/env bash
# Runs all backup-module tests and aggregates the result.
# Usable in CI without Docker/Postgres/GPG — validates the logic with the
# toolchain available (openssl, python3, jq, find, sha256sum).
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

TOTAL_FAIL=0
run() { printf '\n=== %s ===\n' "$1"; if ! bash "$2"; then TOTAL_FAIL=1; fi; }

run "retention (30-day rolling)"       test_retention.sh
run "integrity (sha256 manifest)"      test_integrity.sh
run "crypto (AES-256 roundtrip)"       test_crypto.sh

printf '\n=== restore pipeline (full dump->restore->verify) ===\n'
if python3 test_restore_pipeline.py; then :; else TOTAL_FAIL=1; fi

printf '\n========================================\n'
if [[ "$TOTAL_FAIL" -eq 0 ]]; then
  printf 'ALL BACKUP TESTS PASSED\n'
else
  printf 'BACKUP TESTS FAILED\n'
fi
printf '========================================\n'
exit "$TOTAL_FAIL"
