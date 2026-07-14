# Routing Decision: P2-T01
- Review pass Revision 1; reine Domainvalidierung, medium/low.
- Role Backend Domain Developer; QA Gatekeeper.
- Skills TypeScript, Value Objects, Unit Testing.
- Tier B/A; classes subscription/paid_low; Resource `coding-subscription-01`.
- Max 0,40 Einheiten/30 min; Premium nein.
- Fallback evaluiertes A paid_low; sonst `blocked_budget`.
- Confidence 0,92.

Begründung: keine API/DB/UI-Änderung, deterministischer Oracle. Andere Rollen außerhalb Scope. Gate erfüllt -> `routed`.
