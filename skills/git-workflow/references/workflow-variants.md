# Git Workflow Variants

## GitFlow

Use permanent `main` and `develop`; branch `feature/*` from `develop`, `release/*` from `develop`, and `hotfix/*` from `main`. Merge releases and hotfixes into both permanent branches. Use only when the project explicitly selects this overhead.

## GitHub Flow

Create one short-lived task branch from the protected default branch, open a focused PR, pass checks/review, merge through repository policy, then remove the branch. Keep the default branch deployable.

## Trunk-Based Development

Use very short-lived branches or direct protected-trunk commits as permitted. Integrate small changes frequently; hide incomplete behavior behind an approved feature flag.

## Selection

Follow project context, protected-branch policies and one-task-per-branch discipline. Record the actual strategy in `docs/context/git-rules.md`.
