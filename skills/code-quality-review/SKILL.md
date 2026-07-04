---
name: code-quality-review
description: Review a proposed change for behavioral correctness, scope compliance, logic defects, maintainability, architecture-boundary violations, missing tests, and documentation drift. Use for DevAgency QA, pull-request review, implementation_done review, logic audits, or explicit code-quality assessments; do not use it to implement fixes unless separately authorized.
---

# Code Quality Review

1. Load the unchanged task, specification references, project map, rules, diff, test evidence and Git status.
2. Review findings-first.
3. Trace inputs, state, outputs, errors, null/empty/boundaries, idempotency, concurrency, timeouts and cleanup.
4. Compare every changed file with task scope; classify unrelated changes as scope creep.
5. Check dependency direction, duplication, unnecessary abstraction, observability, documentation and tests.
6. Do not invent requirements or treat preferences as defects.

Report severity, file/line, evidence, impact, violated criterion and remediation direction. Conclude with `pass`, a classified `qa_failed`, or `needs_refinement`. Never edit in review-only mode.
