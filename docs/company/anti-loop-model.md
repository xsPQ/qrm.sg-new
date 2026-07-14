# Anti-Loop Model

```yaml
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: routed
escalation_target: Task Router
failure_evidence: []
```

| Ereignis | Aktion | Owner/Zustand |
|---|---|---|
| 1. Implementierungsfehler | Diagnose + enger Retry | gleicher Agent/in_progress |
| 2. Implementierungsfehler | Reproduktion/Root Cause | Debugging oder stärkeres Tier |
| QA: unklare Aufgabe | nicht weitercodieren | Decomposer/needs_refinement |
| QA: Scope Creep | harte Grenze wiederherstellen | gleicher Coder/in_progress |
| QA: Architektur | Spec/ADR prüfen | Spec Architect/refinement |
| QA: fehlender Test | Testscope präzisieren | Coder/Test Engineer |
| Security Fail | Finding schließen/autorisiert behandeln | Security/Developer |
| Release Fail | Rollback/RC korrigieren | Release Orchestrator |
| 3. inhaltlicher Fehler | Normalschleife endet | split_required/blocked_human |

Provider-/Workspace-Ausfälle zählen separat. Maximal zwei normale Versuche, ein Debugging-Versuch und nur bei begründeter Änderung ein starker Eskalationsversuch. Vor jedem Retry ändert sich Task, Diagnose, Ressource oder Ansatz substanziell.

Failure-Nachweis: Attempt, Symptom, Evidenz, betroffene Kriterien, ausgeschlossene Ursachen, nächster Schritt, Owner und Budgetwirkung.
