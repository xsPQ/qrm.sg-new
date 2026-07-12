# Task BUG-FIX-03: Fehlerkorrektur-Level im Editor read-only

```yaml
phase: P3
workflow_state: task_review
goal: "Das Error-Correction-Dropdown ist im Editor deaktiviert — nur im Creator wählbar. Verhindert, dass Nutzer fälschlicherweise annehmen, das QR-Bild nachträglich zu ändern."
spec_refs: ["Pflichtenheft §3.1.2", "Pflichtenheft §3.7.1", "Pflichtenheft §12.11 BUG-FIX-03"]
risk: low
complexity: low
dependencies: ["qr-design-panel.blade.php (shared)"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: `resources/views/livewire/qr-design-panel.blade.php`, ggf. `QrCreator.php` / `QrCodeEditor.php` für `$isEditor`-Flag
- Out of scope: Download-Controller (bereits korrekt fest auf Medium), Creator-Logik

## Problem

`qr-design-panel.blade.php` ist zwischen Creator und Editor geteilt. Das Error-Correction-Dropdown ist überall editierbar:

```blade
{{-- Line 89-110 --}}
<select id="style-ec"
        wire:model.live="style.error_correction"
        ...>
```

**Warum das falsch ist:** Die Fehlerkorrektur bestimmt, wie viel Redundanz das QR-Bild enthält (L=7%, M=15%, Q=25%, H=30%). Dies wird beim Generieren des QR-Bildes festgelegt. Ein nachträgliches Ändern im Editor ändert nur den DB-Eintrag, nicht aber ein bereits ausgedrucktes Bild. Der Nutzer bekommt einen falschen Eindruck.

## Lösung

### Ansatz: `$isEditor`-Flag im shared Template

1. `QrCodeEditor.php` übergibt (oder macht verfügbar) dass es sich um den Editor-Kontext handelt. Entweder:
   - Eine public Property `$isEditor = true;` im Editor (und `$isEditor = false;` im Creator)
   - Oder eine computed property `getIsEditorProperty()`

2. Im Template:

```blade
<select id="style-ec"
        wire:model.live="style.error_correction"
        @if($isEditor ?? false) disabled @endif
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ ($isEditor ?? false) ? 'bg-gray-100 cursor-not-allowed' : '' }}"
>
```

3. Zusätzlicher Hinweis-Text unter dem Dropdown im Editor:

```blade
@if($isEditor ?? false)
    <p class="mt-1 text-xs text-gray-400">
        🔒 {{ __('Error correction is fixed once the QR code is created and cannot be changed afterwards.') }}
    </p>
@endif
```

### KISS: Minimaler Eingriff

Da das Panel bereits als Partial via `@include` eingebunden wird, reicht es, eine Variable im aufrufenden View zu setzen:

- `qr-creator.blade.php`: `@include('livewire.qr-design-panel', ['isEditor' => false])`
- `qr-code-editor.blade.php`: `@include('livewire.qr-design-panel', ['isEditor' => true])`

Keine Livewire-Property-Änderung nötig, keine Backend-Logik, reiner Blade-Fix.

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Creator: ECC-Dropdown ist auswählbar (L/M/Q/H je nach Plan) | Feature | bestehende Tests + Browser-Check |
| Editor: ECC-Dropdown ist `disabled` | Feature | `test_ecc_dropdown_disabled_in_editor` |
| Editor: Hinweistext "locked" wird angezeigt | Browser | Smoke-Test |
| Bestehende Style-Änderungen (Farbe, Dot-Style) funktionieren weiterhin im Editor | Regression | bestehende Tests |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`, `testing`; Candidate Role: Frontend Developer; Minimum/Preferred Tier: standard; Allowed Classes: low; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
