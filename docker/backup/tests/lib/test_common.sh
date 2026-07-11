#!/usr/bin/env bash
# Test helpers shared by backup test scripts.
set -uo pipefail

TESTS_RUN=0
TESTS_FAIL=0
TEST_DIR=""

new_test_dir() {
  TEST_DIR="$(mktemp -d)"
  trap 'rm -rf -- "$TEST_DIR"' EXIT
}

assert_eq() {  # <expected> <actual> <msg>
  local exp="$1" act="$2" msg="$3"
  TESTS_RUN=$((TESTS_RUN+1))
  if [[ "$exp" == "$act" ]]; then
    printf '  ok   - %s\n' "$msg"
  else
    TESTS_FAIL=$((TESTS_FAIL+1))
    printf '  FAIL - %s (expected [%s] got [%s])\n' "$msg" "$exp" "$act"
  fi
}

assert_ne() {  # <not-this> <actual> <msg>
  local bad="$1" act="$2" msg="$3"
  TESTS_RUN=$((TESTS_RUN+1))
  if [[ "$bad" != "$act" ]]; then
    printf '  ok   - %s\n' "$msg"
  else
    TESTS_FAIL=$((TESTS_FAIL+1))
    printf '  FAIL - %s (got unexpected [%s])\n' "$msg" "$act"
  fi
}

assert_file_exists() {  # <path> <msg>
  local f="$1" msg="$2"
  TESTS_RUN=$((TESTS_RUN+1))
  if [[ -f "$f" ]]; then printf '  ok   - %s\n' "$msg"; else
    TESTS_FAIL=$((TESTS_FAIL+1)); printf '  FAIL - %s (%s missing)\n' "$msg" "$f"; fi
}

finish_suite() {  # <suite-name>
  local name="$1"
  printf '%s: %d run, %d failed\n' "$name" "$TESTS_RUN" "$TESTS_FAIL"
  [[ "$TESTS_FAIL" -eq 0 ]]
}
