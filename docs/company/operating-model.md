# Operating Model

DevAgency arbeitet artefakt- und gatebasiert. Ein Agent verändert nur Zustände, für die sein Rollenvertrag Autorität gibt.

1. **Definition:** Idee -> Pflichtenheft -> Spec Review.
2. **Planung:** Bedarfsanalyse -> Phasen -> atomare Tasks.
3. **Dispatch:** Task Review -> Routing -> Checkout.
4. **Delivery:** Implementierung -> Tests -> QA -> Security.
5. **Release:** RC -> Approval -> Deployment -> Healthcheck.
6. **Learning:** Efficiency Audit -> konkrete Maßnahme.

Details: [process-model.md](process-model.md).

| Entscheidung | Primärrolle | unabhängiges Gate |
|---|---|---|
| Pflichtenheft bereit | Spec Architect | Requirements Analyst |
| Bedarf vollständig | Needs Analyst | Phase Planner |
| Task routingfähig | Task Reviewer | Task Router |
| Ressourcenauswahl | Task Router | Budget Controller bei Ausnahme |
| Implementierung fertig | Developer | Testnachweis |
| QA bestanden | QA Gatekeeper | keine Selbstfreigabe |
| Security bestanden | Security Reviewer | risikobasiert zwingend |
| Release freigegeben | Release Orchestrator | Board/Human nach Policy |

Jeder Heartbeat prüft Identität/Wake-Kontext, wählt nur zugewiesene Arbeit, checkt aus, lädt Rolle/Regeln/Task/Kontext/Budget, wiederholt Grenzen, arbeitet, verifiziert, hinterlegt Evidenz und setzt Status plus nächsten Owner. Parallelität ist nur ohne gemeinsame Schreibbereiche, Blocker oder kollidierende Entscheidungen erlaubt.

Eskalationen nennen Klasse, Evidenz, benötigte Entscheidung, Owner, Kostenwirkung und nächsten erlaubten Status.
