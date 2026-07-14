---
name: task-preflight
description: Validate that a DevAgency task is assigned, atomic, scoped, unblocked, routed, budgeted, and verifiable before any implementation or repository mutation. Use at the beginning of implementation, debugging, test-writing, Docker, migration, or release work, and whenever task readiness or allowed scope is uncertain.
---

# Task Preflight

1. Read `../../AGENTS.md`, the assigned role, and `../../prompts/rules/universal-rules.md`.
2. Load the Paperclip issue, `workflow_state`, goal/parent, blockers, latest wake context, routing decision, attempt history, and budget.
3. Confirm checkout ownership before changing files. Stop on ownership conflict; do not retry it.
4. Verify one goal, concrete file scope, explicit out-of-scope, acceptance criteria, real verification commands, dependencies, required skills, resource class, and cost limit.
5. Read only task-relevant files selected through `../../docs/context/README.md`.
6. Inspect repository and Git state without modifying them.

Return exactly one disposition:

- **ready:** emit the required `Loaded rules` block, summarize task/scope/out-of-scope/verification/budget, then continue.
- **needs_refinement:** name missing or ambiguous fields.
- **split_required:** identify independent goals or layers and propose clean cuts.
- **blocked_dependency**, **blocked_budget**, or **blocked_human:** name owner, unblock action, evidence, and next state.

Never silently rewrite the task or broaden authorization.
