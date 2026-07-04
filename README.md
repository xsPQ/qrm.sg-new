# DevAgency Paperclip AI Template

DevAgency ist ein eigenständiges Betriebssystem für eine KI-native Softwareentwicklungsfirma auf Basis von Paperclip. Es bildet keine menschliche Abteilung nach. Rollen, Regeln, Budgets, Artefakte und Statusübergänge machen Agentenarbeit zerlegbar, routbar, prüfbar und kostensteuerbar.

```text
Idee -> Pflichtenheft -> Bedarfsanalyse -> Phasenplan -> Aufgabenplan
     -> Task-Review -> Routing -> Umsetzung -> Tests -> QA
     -> Security (risikobasiert) -> Release -> Healthcheck -> Auswertung
```

Wenn ein Agent einen Task nicht zuverlässig abschließen kann, werden zuerst Task-Schnitt, Kontext, Verifikation und Routing geprüft. Modellstärke ersetzt keine fehlende Spezifikation.

## Schnellstart

Für ein bereits fertiges Pflichtenheft siehe [How-To-Start.md](How-To-Start.md).

1. Vorhaben in Paperclip als Project mit übergeordnetem Goal anlegen.
2. Issue mit fachlichem `workflow_state: idea` und nativem Status `backlog` erstellen.
3. Pflichtenheft mit [create-specification.md](prompts/sessions/create-specification.md) und [specification-template.md](docs/specification-template.md) erstellen und reviewen.
4. Bedarfsanalyse, Phasenplan und atomare Tasks erzeugen.
5. Tasks unabhängig reviewen und rollen-/ressourcenbasiert routen.
6. Nur geroutete, unblockierte Tasks auschecken und implementieren.
7. Tests, QA, risikobasierte Security-Prüfung, Release und Healthcheck ausführen.
8. Kosten, Fehlschläge und Routing im Efficiency Audit auswerten.

## Paperclip-Kompatibilität

Paperclip verwendet nativ `backlog`, `todo`, `in_progress`, `in_review`, `blocked`, `done` und `cancelled`. DevAgency führt zusätzlich einen präzisen `workflow_state`, etwa `spec_draft`, `task_review`, `routed`, `qa_failed` oder `verified`. Die verbindliche Abbildung steht in [status-model.md](docs/company/status-model.md).

- Arbeit wird vor Beginn ausgecheckt; ein 409-Ownership-Konflikt wird nicht wiederholt.
- Child Issues tragen `parentId` und `goalId`; Abhängigkeiten nutzen `blockedByIssueIds`.
- Reviews nutzen `in_review` mit realem Reviewer-, Approval- oder Interaction-Pfad.
- Jeder Heartbeat endet mit Status, Nachweis und nächstem Owner.
- Rollen sind stabil; Adapter und Modelle sind austauschbare Ressourcen.
- Budget-, Sicherheits- und Produktionsfreigaben werden nicht umgangen.

Referenzen: [Paperclip-Dokumentation](https://docs.paperclip.ing/) und [offizielles Repository](https://github.com/paperclipai/paperclip).

## Pflichtartefakte vor Development

| Artefakt | Vorlage | Gate |
|---|---|---|
| Pflichtenheft | [specification-template.md](docs/specification-template.md) | Anforderungen, Nicht-Ziele, Sicherheit und Abnahme prüfbar |
| Bedarfsanalyse | [needs-analysis-template.md](docs/planning/needs-analysis-template.md) | Stack, Rollen, Skills, Risiken und Budget bestimmt |
| Phasenplan | [phase-template.md](docs/planning/phase-template.md) | Reihenfolge, Abhängigkeiten und Phasenabnahme klar |
| Task | [task-template.md](docs/planning/task-template.md) | atomar, verifizierbar und routingfähig |
| Task-Review | [task-review.md](prompts/sessions/task-review.md) | `pass`, `refine` oder `split` |
| Routing | [routing-decision-template.md](docs/planning/routing-decision-template.md) | Rolle, Tier, Kostenklasse und Fallback begründet |

Ohne abgeschlossenes Pflichtenheft und Bedarfsanalyse darf kein Development-Task `ready` werden.

## Rollen und Ressourcen

Eine Rolle definiert Zweck, Rechte, Inputs, Outputs und Qualitätsvertrag. Eine Ressource definiert Modellfähigkeit, Kosten, Verfügbarkeit und Kontextgrenzen. Rollen werden nie dauerhaft an Modellnamen gebunden.

- [agent-roles.md](docs/company/agent-roles.md) und [prompts/agents/](prompts/agents/)
- [resource-model.md](docs/company/resource-model.md)
- [routing-model.md](docs/company/routing-model.md)
- [model-registry-template.yaml](docs/company/model-registry-template.yaml)

Jeder Development-Agent bestätigt:

```text
Loaded rules:
- one task only
- no scope expansion
- tests required
- no secrets
- no destructive git commands
- update docs if behaviour changes
```

Zusätzlich lädt er Task, Scope, Out-of-Scope, Verifikation, Abhängigkeiten, Budgetklasse und relevanten Kontext. Fehlt etwas, wird verfeinert, geteilt oder blockiert statt geraten.

## Einstiegspunkte

- [TEMPLATE.md](TEMPLATE.md): Manifest und Konsistenzregeln.
- [AGENTS.md](AGENTS.md): verpflichtender Agenteneinstieg.
- [docs/company/](docs/company/): Firma, Prozess, Ressourcen, Status und Metriken.
- [docs/context/](docs/context/README.md): projektspezifischer Laufzeitkontext.
- [docs/planning/](docs/planning/README.md): Planungsartefakte.
- [prompts/](prompts/README.md): Regeln, Sessions, Rollen und Prüfaufträge.
- [skills/](skills/README.md): Agent-Skills-kompatible Arbeitsverfahren.
- [examples/](examples/README.md): konsistente Referenzkette.

Vor produktivem Einsatz werden reale Build-, Test-, Security-, Docker-, Release- und Healthcheck-Kommandos eingetragen und ausgeführt.
