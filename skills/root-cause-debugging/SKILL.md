---
name: root-cause-debugging
description: Reproduce, isolate, diagnose, and minimally fix a DevAgency task failure using evidence-driven hypotheses and anti-loop escalation. Use for failing tests, runtime errors, regressions, flaky behavior, repeated implementation failure, qa_failed implementation defects, or the second unsuccessful coding attempt.
---

# Root Cause Debugging

1. Read task, acceptance, attempts, failure reason, logs, environment, diff and Anti-Loop Model.
2. Reproduce the exact symptom before code changes; record input, expected/actual and frequency.
3. Add or identify a failing regression test when practical.
4. Isolate the boundary and test one falsifiable hypothesis at a time.
5. Distinguish root cause, symptom and provider/workspace failure.
6. Implement only the smallest authorized fix; run narrow and regression gates.
7. Classify and record the attempt.

On the third substantive failure, stop retries and set `split_required` or `blocked_human`. Return reproduction, cause/hypotheses, evidence, verification and next state.
