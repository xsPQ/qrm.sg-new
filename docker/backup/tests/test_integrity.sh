#!/usr/bin/env bash
# Test: integrity manifest (sha256) generation and verification.
# Proves the manifest sha matches a fresh hash, and detects tampering.
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export BACKUP_DB_PASSWORD=x BACKUP_ENCRYPTION_PASSPHRASE=x
. "$SCRIPT_DIR/../bin/lib/common.sh"
. "$SCRIPT_DIR/lib/test_common.sh"

printf 'test_integrity: sha256 manifest + tamper detection\n'
new_test_dir

# Build a fake "encrypted backup" with deterministic content.
printf 'pretend-encrypted-payload' > "$TEST_DIR/blob.gpg"
sha="$(sha256_of "$TEST_DIR/blob.gpg")"
size="$(bytes_of "$TEST_DIR/blob.gpg")"

# Manifest like backup.sh writes.
jq -n --arg sha "$sha" --argjson bytes "$size" \
  '{backup:"blob.gpg", sha256:$sha, bytes:$bytes, cipher:"AES256/GPG-symmetric"}' \
  > "$TEST_DIR/blob.manifest.json"

# 1) Good manifest matches a fresh hash.
fresh="$(sha256_of "$TEST_DIR/blob.gpg")"
manifest_sha="$(jq -r .sha256 "$TEST_DIR/blob.manifest.json")"
assert_eq "$sha" "$fresh" "fresh sha256 reproducible"
assert_eq "$sha" "$manifest_sha" "manifest records correct sha256"

# 2) Tamper: flip a byte, sha must change and mismatch the manifest.
printf 'pretend-encrypted-payloax' > "$TEST_DIR/blob_tampered.gpg"
tampered_sha="$(sha256_of "$TEST_DIR/blob_tampered.gpg")"
assert_ne "$sha" "$tampered_sha" "tampered payload produces different sha256"
if [[ "$tampered_sha" != "$manifest_sha" ]]; then
  assert_eq 1 1 "tampered payload detected via sha mismatch"
else
  assert_eq 0 1 "tampered payload detected via sha mismatch"
fi

# 3) Round-trip a larger random payload, ensure sha stable across re-hash.
head -c 65536 /dev/urandom > "$TEST_DIR/big.gpg"
s1="$(sha256_of "$TEST_DIR/big.gpg")"
s2="$(sha256_of "$TEST_DIR/big.gpg")"
assert_eq "$s1" "$s2" "sha256 stable across repeated hashing of 64KB blob"

finish_suite "test_integrity" || exit 1
