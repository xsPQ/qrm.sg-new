# Status Model

Paperclip akzeptiert nativ nur `backlog`, `todo`, `in_progress`, `in_review`, `blocked`, `done`, `cancelled`. DevAgency führt `workflow_state` als Label, Custom Field oder strukturiertes Dokument.

| workflow_state | nativ | darf setzen | regulär danach |
|---|---|---|---|
| idea | backlog | Strategy | spec_draft, needs_refinement |
| spec_draft | in_progress | Spec Architect | spec_ready, needs_refinement |
| spec_ready | in_review/done | Requirements Reviewer | needs_analysis |
| needs_analysis | in_progress | Needs Analyst | phase_planning, blocked_* |
| phase_planning | in_progress | Phase Planner | task_decomposition, refinement |
| task_decomposition | in_progress | Decomposer | task_review, split_required |
| task_review | in_review | Reviewer | ready, refinement, split |
| ready | todo | Reviewer | routed, blocked_dependency |
| routed | todo | Router | in_progress, blocked_budget |
| in_progress | in_progress | Checkout-Owner | implementation_done, blocked_*, split |
| implementation_done | in_review | Developer/Test | qa_review |
| qa_review | in_review | QA | done, qa_failed, security_failed |
| done | done | QA | release_ready |
| release_ready | in_review/todo | Release | released, release_failed |
| released | done | Release | verified, release_failed |
| verified | done | Release | terminal |
| needs_refinement | blocked/todo | Reviewer/Owner | Planungsschritt |
| split_required | blocked | Reviewer/Debugging/QA | task_decomposition |
| blocked_human | blocked | jede Rolle mit Evidenz | letzter Zustand/cancelled |
| blocked_budget | blocked | Router/Budget | routed/cancelled |
| blocked_dependency | blocked | Owner/Router | ready/routed/in_progress |
| qa_failed | in_progress/blocked | QA | in_progress/decomposition/split |
| security_failed | blocked | Security | in_progress/refinement/cancelled |
| release_failed | blocked | Release | release_ready/in_progress/cancelled |

Checkout setzt nativ `in_progress`. `in_review` braucht realen Reviewpfad. Blocker nennen Owner/Aktion und möglichst `blockedByIssueIds`. Rückwärtsübergänge enthalten Failure Reason, Attempt Counter und Next Allowed State. Nach dem dritten inhaltlichen Fehler nur Split, Human Block oder Abbruch.
