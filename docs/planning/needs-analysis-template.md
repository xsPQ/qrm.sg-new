# Bedarfsanalyse: [PROJECT]

> Zweck: benötigte Technik, Rollen, Skills, Risiken und Budgets aus der freigegebenen Spec ableiten; keine Architektur erfinden.

## Quellen und Scope

- Spec-Revision: `[ref]`
- Ziele/Nicht-Ziele: `[IDs]`
- Annahmen/Fragen: `[mit Owner]`

## Bedarf

| Kategorie | Entscheidung/Bedarf | Begründung | Verifikation/Owner |
|---|---|---|---|
| Sprache/Framework | `[value]` | `[Spec-Bezug]` | `[owner]` |
| Daten/Build/Test | `[value]` | `[reason]` | `[command]` |
| Runtime/Deployment/Docker/CI | `[value]` | `[reason]` | `[owner]` |
| externe APIs | `[value]` | `[contract]` | `[owner]` |

## Rollen, Skills und Ressourcen

| Rolle | Phase/Umfang | Skills | Mindesttier | Klassen | parallel? |
|---|---|---|---|---|---|
| `[role]` | `[scope]` | `[skills]` | `[S-A-B-C]` | `[classes]` | ja/nein |

## Risiken

| Risiko | Wahrscheinlichkeit/Auswirkung | Kontrolle/Test | Security Review | Owner |
|---|---|---|---|---|
| `[risk]` | `[L/M/H]` | `[control]` | ja/nein | `[owner]` |

## Budgetplan

Planung, Zerlegung, Routinecode, kritische Logik, Security, Doku und Release erhalten jeweils Mindesttier, bevorzugte Klasse, Maximalbudget und Fallback.

## Gate

- [ ] Spec ist `spec_ready`; alle Muss-Anforderungen abgedeckt.
- [ ] Stack, Rollen, Skills, Tools, Tests und Betrieb bestimmt.
- [ ] Risiken/Reviews/Approvals markiert.
- [ ] Budgetquellen, Limits und Fallbacks real verfügbar.
- [ ] Keine blockierende Frage.

Nur dann: `phase_planning`.
