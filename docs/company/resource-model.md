# Resource Model

Rollen sind Verträge; Modelle, Adapter, Abos und Budgets sind Ressourcen.

| Klasse | Bedeutung | Nutzung |
|---|---|---|
| `free_tier` | kostenlos, variabel | Vorfilter, Doku, risikoarme Checks |
| `subscription` | im Abo enthalten | bevorzugte Standardentwicklung |
| `paid_low` | geringe variable Kosten | Routinecode, Tests, kleine Refactorings |
| `paid_standard` | starke bezahlte Ressource | Produktlogik, Debugging, Integrationen |
| `paid_premium` | teuer/spitzenfähig | kritische Planung, Security, Eskalation |
| `reserved_budget` | zweckgebundener Topf | freigegebene Provider/Taskklassen |

| Tier | Bedeutung | Einsatz |
|---|---|---|
| S | höchste nachgewiesene Fähigkeit | kritische Architektur/Security/Datenverlust |
| A | starke verlässliche Umsetzung | normale Entwicklung/Review/Integration |
| B | gute Routinefähigkeit | Tests, Doku, kleine UI/Config |
| C | eingeschränkt/variabel | Klassifikation/Voranalyse mit Folge-Gate |

Tier und Kostenklasse sind unabhängig. Budgetquellen können OpenRouter, z.ai Coding Plan, OpenAI Codex Abo und DeepSeek Budget sein. Aktuelle Limits werden geprüft, nicht angenommen.

Das Register erfasst Resource-ID, Slug, Adapter, Fähigkeiten, Tier je Workload, Kostenklasse, Kontext, Tools/Vision, Evaldatum, Erfolgsquote, Preis, Budgetquelle, Ausschlüsse und Fallbacks. Beispiel-Slugs wie `anthropic/claude-fable-5`, `openai/gpt-5.5`, `anthropic/claude-opus-4.7`, `openai/gpt-oss-120b:free`, `google/gemini-3.1-pro-preview`, `moonshotai/kimi-k2.7-code`, `google/gemma-4-31b:free`, `deepseek/deepseek-v4-flash`, `openai/gpt-oss-20b:free`, `anthropic/claude-sonnet-4.6` und `openrouter/free` bleiben deaktiviert, bis Existenz, Preis und Leistung verifiziert sind.

Auswahl: Mindestfähigkeit bestimmen, ungeeignete Ressourcen filtern, Subscription/Free bei passender Zuverlässigkeit bevorzugen, günstigste ausreichende Ressource wählen, Premium-Trigger und Fallback vorher definieren.

Premium ist nur für kritische Architektur, Security, Datenverlust, schwierige Zerlegung, wiederholten klassifizierten Fehler oder Produktion erlaubt. Free Tier trifft nie allein die Letztentscheidung über Security, Auth, Migration, Zahlung/Lizenz oder Release.

Vor Lauf: Maximalbetrag/Token-/Zeitlimit. Bei 80 % Budget nur kritischen Scope; bei 100 % stoppen. Kein Sunk-Cost-Retry ohne Fehlerklassifikation.
