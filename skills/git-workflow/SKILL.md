---
name: git-workflow
description: Execute safe, traceable Git branch, commit, pull-request, merge, tag, and handoff steps according to the repository-selected workflow. Use whenever a DevAgency task requires Git operations, commit preparation, branch selection, GitFlow, GitHub Flow, trunk-based development, PR metadata, or repository hygiene.
---

# Git Workflow

1. Read `../../docs/context/git-rules.md` and `../../prompts/rules/git-rules.md`.
2. Run `git status --short --branch`; preserve pre-existing and unrelated changes.
3. Identify the configured strategy. If unspecified, do not assume GitFlow.
4. Read [references/workflow-variants.md](references/workflow-variants.md) for the selected strategy.
5. Keep branch, diff and commits limited to the current task; stage explicit paths.
6. Run task verification, `git diff --stat`, `git diff`, and final status.
7. Use `<type>: <description>` and required task/Paperclip references.
8. Push, PR, merge, branch deletion or tag only when explicitly authorized.

Never rewrite shared history, force-push, discard user changes, or commit secrets, local data, generated output or release artifacts without authorization.
