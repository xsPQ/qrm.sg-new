# Routing Decision: [TASK-ID]

> Zweck: auditierbare Zuordnung von Rolle und austauschbarer Ressource.

- Review-Revision/Tasktyp/Risiko/Komplexität/Kontext: `[values]`
- Required Skills/Tools: `[values]`
- Selected Role/Secondary Reviewers: `[roles]`
- Minimum/Preferred Tier: `[tier]`
- Allowed/Selected Resource Class: `[classes]`
- Selected Registry Resource: `[id, not role binding]`
- Max Cost/Timeout: `[limits]`
- Premium Allowed/Reason: `[bool/reason]`
- Fallback: `[resource or blocked_budget]`
- Confidence: `[0..1]`
- Rationale/Rejected Alternatives: `[evidence]`

Gate: Task review pass, Ressource aktiv/evaluiert/verfügbar, Limits innerhalb Budget, Confidence ≥0,70, keine Rollenkollision. Nur dann `routed`.
