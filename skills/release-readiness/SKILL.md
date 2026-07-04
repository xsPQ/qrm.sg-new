---
name: release-readiness
description: Prepare and gate a DevAgency release candidate through scope freeze, versioning, full verification, security, configuration, migration, rollback, artifact integrity, approvals, deployment evidence, and healthcheck. Use for release preparation, deployment approval, release_failed recovery, or deciding whether completed work can be released.
---

# Release Readiness

1. Read release rules/checklist, done-task scope, security results and approvals.
2. Freeze and enumerate scope; reject unreviewed work.
3. Confirm version/changelog, tests/build/lint/security/dependencies, docs, config and secrets.
4. Confirm migration, backup, rollback command/criteria and operational owner.
5. Build once; record immutable artifact identity and required provenance/SBOM.
6. Verify approval before deployment.
7. After deployment, record environment/version and run readiness, smoke, logs/metrics and rollback signals.

Return `release_ready`, `released`, `verified`, or `release_failed` with evidence and owner. Never bypass gates or retry without diagnosis.
