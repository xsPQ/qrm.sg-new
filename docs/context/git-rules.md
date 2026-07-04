# Project Git Rules

Projektworkflow: **GitHub Flow**. Remote: `https://github.com/xsPQ/qrm.sg-new.git`. Geschützter Basisbranch: `main`. Branchschema: `<type>/<paperclip-id>-<slug>`, zum Beispiel `docs/QRM-12-needs-analysis` oder `feat/QRM-42-resolver-cache`.

Jeder Task startet von einem aktuellen `main` in einem kurzlebigen Branch. Änderungen werden über einen fokussierten Pull Request mit grünen Quality Gates und dem laut Task erforderlichen Review nach `main` übernommen. `main` bleibt jederzeit reproduzierbar und grundsätzlich auslieferbar; direkte Produktänderungen auf `main` sind nach dem Baseline-Commit nicht vorgesehen.

Vor Arbeit: `git status --short --branch` und `git fetch origin`; der Task-Branch basiert auf `origin/main`. Vor Abschluss: `git diff --stat`, `git diff`, reale Task-Verifikation und `git status --short --branch`. Ein Commit bildet einen Task oder logisch isolierten Teil ab und folgt `<type>: <description>`.

Nie fremde Änderungen zurücksetzen, Shared History umschreiben, force-pushen oder `reset --hard/clean -fdx` ohne Auftrag. Push, Merge, Tag und PR nur wenn Task/Workflow sie autorisiert. Keine Secrets, Dumps, Builds oder lokalen Daten. Branches werden erst nach erfolgreichem Merge und nur durch den vorgesehenen Workflow entfernt.
