# Process Model

Ein Gate ohne Evidenz gilt als nicht bestanden.

| # | Schritt | Eingang | Ausgang | Agent | Regeln/Gate | Fehler/Eskalation |
|---:|---|---|---|---|---|---|
| 1 | Idee erfassen | Wunsch/Problem | Goal + Idea Brief | Strategy Coordinator | Nutzen, Owner, Nicht-Ziel klar | needs_refinement -> fachlicher Owner |
| 2 | Pflichtenheft | Idea Brief/Quellen | Spec Draft | Spec Architect | IDs, Abnahme, keine erfundenen Fakten | spec_draft -> Requirements Analyst |
| 3 | Spec prüfen | Draft | Reviewentscheid | Requirements Analyst | Konsistenz, Testbarkeit, Traceability | needs_refinement -> Spec Architect |
| 4 | Bedarfsanalyse | spec_ready | Needs Analysis | Needs Analyst | Stack, Rollen, Skills, Risiko, Budget | blocked_* -> Strategy/Budget |
| 5 | Phasenplan | Spec + Bedarf | Phase Plan | Phase Planner | abnehmbare Wertschnitte/Abhängigkeiten | needs_refinement -> Strategy |
| 6 | Aufgabenplan | Phase | atomare Tasks | Task Decomposer | ein Ziel, klare Tests/Grenzen | split_required -> Phase Planner |
| 7 | Task-Review | Tasks | pass/refine/split | Task Reviewer | unabhängig, atomar, routbar | refinement/split -> Decomposer |
| 8 | Routing | reviewter Task/Register | Routing Decision | Task Router | Rolle vor Modell, Limit/Fallback | blocked_budget -> Budget Controller |
| 9 | Dev vorbereiten | gerouteter Task | Checkout + Loaded Rules | Cartographer/Developer | Repo, Git, Kontext, Blocker geprüft | blocked_dependency -> Router |
| 10 | Implementieren | ausführbarer Task | kleiner Diff + Doku | Fachentwickler | nur Scope, vorhandene Muster | in_progress -> Debugging nach Regel |
| 11 | Tests | Implementierung | Test-/Buildnachweis | Developer/Test Engineer | erforderliche Tests/Regression grün | klassifizieren -> Developer/Debugging |
| 12 | QA | Diff, Task, Evidenz | QA Decision | QA Gatekeeper | gegen Task, nicht Wunschliste | qa_failed -> Anti-Loop-Matrix |
| 13 | Security | Risikotag | Security Decision | Security Reviewer | negative Pfade, Auth, Daten, Secrets | security_failed -> Dev/Architecture |
| 14 | Release vorbereiten | done Scope | RC | Release Orchestrator | Version, Changelog, Migration, Rollback | release_failed -> Strategy/DevOps |
| 15 | Release | freigegebener RC | Deployment-Evidenz | Release/DevOps | Approval, reproduzierbar, keine Secrets | release_failed -> Incident/Human |
| 16 | Healthcheck | Release | Health-Evidenz | Release Orchestrator | Smoke, Logs, kritische Flows | release_failed -> Rollback |
| 17 | Efficiency Audit | Kosten/Runs/Failures | Maßnahmenbericht | Efficiency Auditor | Ursache, Owner, Zielmetrik | blocked_human -> Strategy |

Spec und Bedarfsanalyse sind harte Vorgänger. Security ist zwingend bei Auth, Autorisierung, Mandanten, PII, Secrets, Zahlung/Lizenz, Migration, Dateizugriff, externer API und Produktion. Manuelle Checks nennen Schritte, Erwartung, Prüfer und Datum.
