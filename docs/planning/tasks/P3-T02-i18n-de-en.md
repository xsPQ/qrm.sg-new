# Task P3-T02: Deutsch/Englisch-Lokalisierung vervollständigen

```yaml
phase: P3
workflow_state: task_review
goal: "Die öffentlich sichtbaren Kernflüsse und E-Mails sind auf Deutsch und Englisch lokalisierbar, inklusive sauberem Fallback."
spec_refs: ["Pflichtenheft §12.3", "Pflichtenheft §9.4", "Pflichtenheft §7.6"]
risk: medium
complexity: medium
dependencies: ["bestehende Views, Mails und Validation-Strings"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: `lang/de/*`, `lang/en/*`, relevante Blade-/Livewire-/Mailable-Strings
- Out of scope: automatische Übersetzung, Machine Translation, neue Locale-Persistence-Architektur

## Verhalten

| Aspekt | Vertrag |
|---|---|
| Inputs/Preconditions | gespeicherte Nutzerpräferenz oder Browser-Sprache |
| Outputs/Side effects | DE/EN Texte für UI, Fehler und Mails |
| Edge cases | fehlende Keys, gemischte Sprachen, Fallback auf Englisch |
| Errors | fehlende Übersetzung darf keine 500er erzeugen |

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Wichtige Kernseiten sind in DE und EN sichtbar | Feature/Browser-Smoke | manuelle + automatisierte Checks |
| Fehlende Übersetzungen fallen sauber auf Englisch zurück | Feature | gezielter Locale-Test |
| E-Mail-Templates geben keine Hardcoded Mixed-Language-Fragmente aus | Feature | Snapshot-/Mail-Assertions |

## Routingrahmen

Required Skills: `task-definition`, `laravel`, `testing`; Candidate Role: Frontend UI Developer; Minimum/Preferred Tier: standard; Allowed Classes: medium; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
```