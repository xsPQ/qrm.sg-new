# Backup & Restore (qrm.sg)

Pflichtenheft §7.2 — _„Täglich verschlüsselt, 30 Tage Aufbewahrung, mindestens
eine Kopie außerhalb des Produktionsservers, Wiederherstellung getestet."_

This document is the binding operations runbook for the backup service
(`docker/backup`). It covers setup, daily behaviour, retention, manual restore,
the automated restore test, and troubleshooting.

---

## 1. Architecture

| Component | Tool | Notes |
|---|---|---|
| Dump | `pg_dump --format=custom` | Binary, parallel-restorable, selective |
| Compress | `gzip` | Applied before encryption |
| Encrypt | `gpg --symmetric --cipher-algo AES256` | Symmetric passphrase, never on disk |
| Integrity | `sha256sum` + JSON manifest | Verified before trusting a backup |
| External copy | `aws s3 cp` | S3-compatible (AWS S3, MinIO, R2, Wasabi) |
| Retention | `find -mtime` (local) + `aws s3 rm` (remote) | Rolling 30 days, both stores |
| Schedule | `supercronic` | No-root cron inside the container |
| Restore test | `restore-test.sh` | Decrypt → temp DB → row-count check |

**File layout** of one backup (`/backups` inside the container):

```
qrm-20260710T020000Z.dump.gz.gpg     # encrypted compressed dump
qrm-20260710T020000Z.manifest.json   # {sha256, bytes, timestamp, cipher, ...}
```

### Secrets (runtime only — never committed)

| Variable | Purpose | Example |
|---|---|---|
| `BACKUP_DB_PASSWORD` | Password of the dump/restore DB role | — |
| `BACKUP_ENCRYPTION_PASSPHRASE` | GPG symmetric passphrase | `openssl rand -base64 32` |
| `BACKUP_S3_ACCESS_KEY` | S3 access key | — |
| `BACKUP_S3_SECRET_KEY` | S3 secret key | — |

> **Critical:** store `BACKUP_ENCRYPTION_PASSPHRASE` **offline** (password
> manager / vault). Without it no backup can ever be restored.

---

## 2. Setup

1. Build the image:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.backup.yml build backup
   ```
2. Provision secrets in the runtime secret store / `.env` (not in git):
   ```bash
   openssl rand -base64 32   # -> BACKUP_ENCRYPTION_PASSPHRASE
   ```
3. Create the S3-compatible bucket and set `BACKUP_S3_URI`
   (e.g. `s3://qrm-backups/qrm-sg/daily`).
4. Start the service:
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.backup.yml up -d backup
   ```

---

## 3. Daily behaviour

- **02:00 Europe/Berlin** (`BACKUP_SCHEDULE`): `backup.sh` runs
  dump → compress → encrypt → manifest → S3 upload → prune → health marker.
- **Sunday 03:00** (`BACKUP_RESTORE_TEST_SCHEDULE`): `restore-test.sh` runs
  the automated restore test (see §5).
- Local logs: `/var/log/backup/daily.log`, `/var/log/backup/restore-test.log`.
- Container `HEALTHCHECK` reports unhealthy if the last successful backup is
  older than `RETENTION_DAYS - 1`.

---

## 4. Retention (30 days)

Both the local store and the remote S3 prefix are pruned to a rolling 30-day
window after every run:

- Local: `find /backups -name 'qrm-*.gpg' -mtime +30 -delete`
- Remote: objects under `BACKUP_S3_URI` whose `LastModified` is older than 30
  days are deleted.

Retention length is configurable via `BACKUP_RETENTION_DAYS`.

---

## 5. Restore test (automated)

`restore-test.sh` is the **documented Wiederherstellungstest** required by §7.2.

**Procedure (runs automatically every Sunday):**

1. Snapshot row-counts of the core business tables from the **live** database:
   `users, qr_codes, qr_code_routes, scans, scan_stats_hourly,
   scan_stats_daily, subscriptions, subscription_items,
   admin_action_logs, stripe_webhook_events`.
2. Create a throwaway database `qrm_restoretest_<unix_ts>`.
3. Decrypt the latest backup (`gpg`), decompress (`gunzip`).
4. `pg_restore` into the temp database (`--no-owner --exit-on-error`).
5. Snapshot row-counts from the **restored** database.
6. **Compare** source vs restored per table. `PASS` only on an exact match for
   every table.
7. Drop the temp database (always — even on failure).
8. Write a structured result:
   - `/backups/restore-tests/restore-test-<ts>.log` (human-readable)
   - `/backups/restore-tests/restore-test-<ts>.json`
     (`{status:"PASS"|"FAIL", mismatches, subject, temp_database}`)

**Manual run:**
```bash
docker compose -f docker-compose.yml -f docker-compose.backup.yml \
  exec backup /usr/local/bin/restore-test.sh
```
A `PASS` result is the acceptance proof that a restore has been tested.

---

## 6. Manual restore (disaster recovery)

```bash
# List available backups
docker compose ... exec backup sh -lc 'ls -t /backups/*.gpg | head'

# Restore a specific backup into the live database (destructive: --clean)
docker compose ... exec backup /usr/local/bin/restore.sh qrm-20260710T020000Z

# Or restore into a separate DB first for inspection
docker compose ... exec backup /usr/local/bin/restore.sh qrm-20260710T020000Z --target-db qrm_inspect
```

Target RTO per §7.2: **< 4 hours**. A fresh dump+encrypt+upload of this dataset
takes minutes; restore time is dominated by transfer + `pg_restore`.

---

## 7. Troubleshooting

| Symptom | Cause / Fix |
|---|---|
| `backup failed: E_DUMP` | Postgres unreachable / wrong role → check `BACKUP_DB_*` and that `postgres` is healthy |
| `E_ENCRYPT` / `E_VERIFY` | Wrong passphrase or corrupt file → re-run; verify manifest sha256 |
| `E_UPLOAD` | S3 creds/endpoint/bucket → run `aws s3 ls $BACKUP_S3_URI` manually |
| HEALTHCHECK unhealthy | No backup in `RETENTION-1` days → run `backup.sh` manually, check `daily.log` |
| restore-test FAIL | Row-count mismatch → inspect `.log`; a schema drift or failed prior backup is typical |
