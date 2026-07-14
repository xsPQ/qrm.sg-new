---
name: security-review
description: Perform a risk-based application and delivery security review covering trust boundaries, authentication, authorization, input/output handling, secrets, sensitive data, dependencies, files, containers, CI, and deployment. Use for explicit security audits and whenever a DevAgency task touches auth, tenants, PII, payments, licensing, uploads, paths, migrations, external APIs, secrets, or production.
---

# Security Review

1. Read security context/rules, task/specification, trust boundaries, diff, scans and tests.
2. Identify assets, actors, untrusted inputs and privilege transitions.
3. Test auth/authz, injection/encoding, path/upload, secrets, sensitive logs, cryptography, dependencies, container/CI permissions and failure behavior as applicable.
4. Prefer negative tests and reproducible, redacted evidence.
5. Separate vulnerabilities, hardening suggestions, false positives and accepted risks.

Report severity, asset, preconditions, exploit path, evidence, impact, remediation and retest. Unresolved Critical/High sets `security_failed` unless authorized time-bounded acceptance exists.
