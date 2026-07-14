# Agent Roles

Rollen sind Autoritätsverträge, keine Modellaliases. Jeder Einzelvertrag unter [prompts/agents/](../../prompts/agents/) enthält alle Pflichtfelder.

| Rolle | Kernverantwortung | verbotene Selbstfreigabe |
|---|---|---|
| Strategy Coordinator | Priorität/Goal/Eskalation | fachliche Annahmen/Umsetzung |
| Spec Architect | Pflichtenheft | eigenes Spec-Gate |
| Requirements Analyst | Testbarkeit/Traceability | Anforderungen erfinden |
| Needs Analyst | Stack/Skills/Risiko/Ressourcen | Architektur implementieren |
| Phase Planner | Phasen/Abhängigkeiten | Tasks codieren |
| Task Decomposer | atomare Tasks/Testmatrix | eigenes Review |
| Task Reviewer | Atomicity/Verifikation | implementieren |
| Task Router | Rollen-/Ressourcenwahl | Budgetausnahme |
| Repository Cartographer | Repo-Karte/Grenzen | Produktlogik ändern |
| Backend Domain Developer | Domain/Services/Policies | fremde Layer erweitern |
| API Developer | API-Verträge/Controller | Domain neu definieren |
| Database Developer | Schema/Migration/Queries | riskante Migration ohne Gate |
| Frontend UI Developer | Komponenten/Flows/A11y | Backend erweitern |
| Integration Developer | APIs/Events/Jobs | ungeprüfte Credentials |
| Test Engineer | Tests/Fixtures/Regression | Verhalten umdeuten |
| Debugging Specialist | Reproduktion/Root Cause | Shotgun-Fixes |
| Security Reviewer | Threats/Auth/Daten/Secrets | Risiko still akzeptieren |
| DevOps Docker Agent | Container/CI/Deploy | Produktion ohne Approval |
| Documentation Agent | Nutzer-/Betriebsdoku | Verhalten erfinden |
| QA Gatekeeper | unabhängiges Pass/Fail | selbst fixen/Scope erweitern |
| Release Orchestrator | RC/Rollout/Rollback/Health | Gates überschreiben |
| Budget Controller | Limits/Premium/Kosten | Priorität allein ändern |
| Efficiency Auditor | Ursachen/Verbesserungen | Policies ungeprüft ändern |

Coder und QA, Spec Author und Reviewer sowie Router und Budget-Ausnahmegenehmiger bleiben pro Task getrennt.

## Standard-Skill-Zuordnung

| Rollenfamilie | Skills |
|---|---|
| alle ausführenden Rollen | `task-preflight` |
| Development/Documentation | `git-workflow`, `test-and-verify` nach Änderung |
| Task Reviewer/QA | `code-quality-review`, `test-and-verify` |
| Security/Risiko-Owner | `security-review` |
| Debugging Specialist | `root-cause-debugging`, `test-and-verify` |
| DevOps Docker Agent | `docker-delivery`, `git-workflow`, `test-and-verify` |
| Release Orchestrator | `release-readiness`, optional `docker-delivery` |

Skills werden per Shortname zugewiesen und verleihen keine zusätzlichen Status-, Budget- oder Produktionsrechte.
