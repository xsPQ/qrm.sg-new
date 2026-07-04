---
name: test-and-verify
description: Derive, implement, run, and report risk-based verification for code, configuration, documentation behavior, bug fixes, migrations, APIs, UI flows, and release changes. Use when a DevAgency task changes behavior, requires tests, approaches implementation_done, or needs objective acceptance evidence.
---

# Test and Verify

1. Read acceptance criteria, `../../docs/context/testing-rules.md`, testing rules and project commands.
2. Map every changed function/flow and criterion with the Function/Test Matrix.
3. Select the lowest layer that proves behavior: unit, integration, then E2E/smoke.
4. Cover happy, relevant edge and error paths; bugfixes need regression tests.
5. Keep tests isolated, deterministic, non-production, and behavior-focused.
6. Run narrow tests first, then broader gates required by risk.
7. Record commands, exit codes, results, skipped checks and residual risk.

Manual checks require reason, exact steps, expectation, environment and reviewer/date. Never claim an unrun check passed.
