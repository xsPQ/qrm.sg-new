# Metrics

| Bereich | Kennzahl | Zweck |
|---|---|---|
| Flow | Lead Time, Gate Time, Blocked Time | Engpass |
| Task | Refinement-/Split-Rate | Zerlegungsqualität |
| Qualität | First-pass QA, QA/Security/Release-Fails | Zuverlässigkeit |
| Tests | Anforderungen mit Nachweis, Regressionen | Traceability |
| Routing | First-route Success, Reassignments, Calibration | Routinggüte |
| Kosten | Kosten/done Task, Retry-/Premium-Anteil | Wirtschaftlichkeit |
| Ressourcen | Erfolg/Kosten/Latenz je Tier/Klasse | Registerpflege |
| Betrieb | Healthcheck/Rollback/Incidents | Releasequalität |

Segmentiere nach Tasktyp, Risiko und Komplexität. Zeige Stichprobe und Evaldatum. Trenne Human-, Budget- und Dependency-Blockzeit. `done` ohne Nachweis zählt nicht. Audits erzeugen wenige Maßnahmen mit Owner, Baseline, Ziel und Reviewdatum.

Zielwerte: ≥80 % First-pass QA bei ready Tasks; 100 % geänderte Funktionen mit Test oder genehmigter Begründung; <10 % ungeplante Premium-Läufe; 0 Tasks mit mehr als drei Fehlern außerhalb Split/Human Block.
