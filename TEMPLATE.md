# DevAgency Template Manifest

> Root authority für Struktur, Quellenhierarchie, Abhängigkeiten und Pflege.

## Leitprinzipien

- Spezifikation vor Development; Bedarfsanalyse vor Ressourcenbindung.
- Ein fachliches Ziel pro Task.
- Rollen und Modellressourcen bleiben getrennt.
- Paperclip ist Control Plane; Repository, Runtime und CI bilden die Execution Plane.
- Kein Status ohne Nachweis, kein Blocker ohne Owner, kein Review ohne realen Review-Pfad.
- Fehlschläge werden klassifiziert und gezielt geroutet statt blind wiederholt.

## Quellenhierarchie

1. aktueller Auftrag und genehmigte Approvals,
2. Pflichtenheft und akzeptierte ADRs,
3. freigegebener Task samt Grenzen und Akzeptanz,
4. projektspezifische Dateien unter `docs/context/`,
5. Regelpakete unter `prompts/rules/`,
6. Rollenvertrag unter `prompts/agents/`,
7. Beispiele.

Abweichungen werden als ADR, Refinement oder Approval dokumentiert.

## Verzeichnisvertrag

```text
DevAgency/
├── README.md, AGENTS.md, TEMPLATE.md
├── docs/
│   ├── specification-template.md
│   ├── company/       # Steuerungsmodelle
│   ├── context/       # knapper Projektkontext
│   ├── planning/      # Planungsartefakte
│   └── adr/           # Entscheidungen
├── prompts/
│   ├── rules/         # harte Regeln
│   ├── sessions/      # Prozessabläufe
│   ├── agents/        # Rollen ohne Modellbindung
│   └── tasks/         # Prüfaufträge
├── skills/             # ausführbare rollenübergreifende Verfahren
├── examples/
└── scripts/
```

## Artefaktfluss

| Eingang | Ausgang | Eigentümer | Gate |
|---|---|---|---|
| Idee | Pflichtenheft | Spec Architect | Spec Review |
| Pflichtenheft | Bedarfsanalyse | Needs Analyst | Bedarf vollständig |
| beide | Phasenplan | Phase Planner | Phasenabnahme |
| Phase | Tasks | Task Decomposer | atomare Tasks |
| Tasks | Review | Task Reviewer | pass/refine/split |
| reviewter Task | Routing | Task Router | Rolle, Ressource, Limit |
| gerouteter Task | Code + Tests | Development-Rolle | Akzeptanz |
| Implementierung | QA | QA Gatekeeper | pass/klassifiziertes fail |
| Release-Scope | Release-Nachweis | Release Orchestrator | Healthcheck |
| Laufdaten | Efficiency Audit | Efficiency Auditor | Maßnahme mit Owner |

## Paperclip-Abbildung

| DevAgency | Paperclip |
|---|---|
| Firma | Company |
| Vorhaben | Project mit Goal |
| Phase | Parent Issue oder Issue Document |
| Task | Issue/Child Issue |
| Plan/Spezifikation | Issue Document oder versionierte Datei |
| Blocker | `blockedByIssueIds` und `blocked` |
| Review | `in_review` plus Reviewer/Approval/Interaction |
| Agentenlauf | Heartbeat/Run mit Checkout |
| Ergebnis | Attachment/Artifact/Work Product |
| Prozesszustand | `workflow_state` als Metadatum/Label/Dokument |

Native Statuswerte werden nicht durch erfundene API-Werte ersetzt.

## Pflichtmetadaten eines Tasks

```yaml
id: P2-T04
workflow_state: ready
goal: "Ein fachlich prüfbares Ergebnis"
scope: {create: [], modify: [], delete: []}
out_of_scope: []
acceptance_criteria: []
verification: {commands: []}
dependencies: []
routing:
  role: null
  minimum_tier: B
  resource_class: subscription
  max_estimated_cost: null
attempt:
  count: 0
  failure_reason: null
  current_owner: null
  next_allowed_state: routed
  escalation_target: Task Router
```

## Konsistenzmatrix

| Änderung | Mitprüfen |
|---|---|
| Prozess/Gate | Operating-, Process-, Statusmodell und Sessions |
| Status | Statusmodell, Vorlagen, Gates, Beispiele |
| Rolle | Rollenübersicht, Einzelrolle, Routing, Index |
| Skill/Zuweisung | `skills/README.md`, Rollen, AGENTS und Prompt-Index |
| Ressourcenklasse/Tier | Ressourcenmodell, Register, Routing, Budget Controller |
| Git/Docker/Test/Security/Release | gleichnamige Kontext- und Promptdateien |
| Task-Schema | beide Task-Templates, Matrix, Beispiele, Sessions |
| Datei/Verzeichnis | README, Manifest und zuständiger Index |

## Einsatzreife

Alle Links existieren; 23 Rollen sind vollständig; keine Rolle ist modellgebunden; 17 Prozessschritte besitzen Eingang, Ausgang, Agent, Regeln, Gate, Fehler und Eskalation; Statusabbildung und Anti-Schleifen sind eindeutig; Vorlagen erklären Zweck, Ausfüllung, Qualität und Folge-Gate; Beispiele sind rückverfolgbar; Projektkommandos sind validiert.
