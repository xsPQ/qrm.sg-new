# How-To: Neues Projekt aus einem fertigen Pflichtenheft starten

Diese Anleitung setzt ein ausgearbeitetes Pflichtenheft voraus. Ziel ist, Paperclip mit der DevAgency-Vorlage von der Bedarfsanalyse bis zu ausführbaren Tasks zu starten — noch ohne vorschnelles Coding.

## Voraussetzungen

- DevAgency-Regeln, Rollen und Skills sind für die Paperclip-Company verfügbar.
- Das Pflichtenheft liegt als Datei, Attachment oder Issue Document vor.
- Ein Strategy Coordinator, Requirements Analyst, Needs Analyst, Phase Planner, Task Decomposer, Task Reviewer und Task Router sind zuweisbar.
- Budgetquellen und das Modellregister sind mindestens initial konfiguriert.

## In Paperclip anlegen

1. Ein neues **Project** für das Produkt anlegen.
2. Ein übergeordnetes **Goal** mit dem fachlichen Projektziel anlegen.
3. Ein Start-Issue erstellen:
   - nativer Status: `todo`
   - `workflow_state: spec_ready`
   - Assignee: Strategy Coordinator
   - Project und Goal verknüpfen
   - Pflichtenheft anhängen oder als Issue Document `specification` hinterlegen
4. Den folgenden Auftrag als Issue-Beschreibung oder ersten Kommentar verwenden.

## Kopierbarer Startauftrag

```text
Starte dieses Projekt nach dem DevAgency Paperclip AI Template.

Verbindliche Grundlage:
- das diesem Issue beigefügte Pflichtenheft
- DevAgency/AGENTS.md
- DevAgency/TEMPLATE.md
- DevAgency/docs/company/operating-model.md
- DevAgency/docs/company/process-model.md
- DevAgency/docs/company/status-model.md
- DevAgency/docs/company/resource-model.md

Ausgangslage:
- Das Pflichtenheft ist fachlich ausgearbeitet.
- Prüfe zuerst kurz und read-only, ob das Specification-Gate tatsächlich erfüllt ist.
- Erfinde keine fehlenden Anforderungen.
- Bei einer blockierenden Lücke: workflow_state = needs_refinement und benenne Owner sowie konkrete Entscheidung.

Wenn das Specification-Gate erfüllt ist:
1. Setze bzw. bestätige workflow_state = spec_ready.
2. Erstelle die Bedarfsanalyse nach
   DevAgency/docs/planning/needs-analysis-template.md.
3. Erstelle danach den Phasenplan nach
   DevAgency/docs/planning/phase-template.md.
4. Zerlege nur die erste freigegebene Phase in atomare Tasks nach
   DevAgency/docs/planning/task-template.md.
5. Lasse jeden Task unabhängig reviewen.
6. Route nur reviewte Tasks mit
   DevAgency/docs/planning/routing-decision-template.md.
7. Erstelle Tasks als Paperclip Child Issues mit parentId, goalId,
   Dependencies/blockedByIssueIds und dem passenden workflow_state.
8. Weise Rollen vor Modellressourcen zu. Modelle bleiben austauschbar.
9. Nutze Premium-Ressourcen nur nach Ressourcen- und Budgetregel.
10. Starte noch keine Implementierung, bevor Pflichtenheft,
    Bedarfsanalyse, Phase, Task-Review und Routing abgeschlossen sind.

Jeder erzeugte Task muss enthalten:
- genau ein Ziel
- Spec- und Abnahmereferenzen
- Scope und Out-of-Scope
- konkretes Verhalten und Fehlerfälle
- erforderliche Tests und reale Verifikationsbefehle
- Abhängigkeiten und Security-Trigger
- Rolle, Mindesttier, Ressourcenklasse, Kostenlimit und Fallback
- Attempt Counter, Current Owner, Next Allowed State und Escalation Target

Erwartete erste Ausgabe:
- Ergebnis des Specification-Gates
- Bedarfsanalyse
- Phasenplan
- Tasks der ersten Phase mit Reviewstatus
- Routingentscheidungen für alle ready Tasks
- Blocker, offene Approvals und Budgetbedarf
- klare Aussage, welcher Task als erster implementierbar ist

Beende jeden Heartbeat mit Evidenz, gültigem Paperclip-Status,
workflow_state und eindeutigem nächsten Owner.
```

## Erwarteter Zustand vor dem ersten Coding-Heartbeat

- Specification-Gate bestanden.
- Bedarfsanalyse abgeschlossen.
- Erste Phase freigegeben.
- Mindestens ein atomarer Task ist `ready`, reviewt und `routed`.
- Blocker, Budget, Skill-Zuordnung und Verifikationsbefehle sind bekannt.
- Der Development-Agent beginnt mit dem Skill `task-preflight`.

Erst dann darf Checkout und Umsetzung beginnen.
