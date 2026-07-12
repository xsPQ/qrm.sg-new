# Task BUG-FIX-01: Passwort-Eingabe im Creator

```yaml
phase: P3
workflow_state: task_review
goal: "Der Nutzer kann ein Passwort bereits bei der Erstellung des QR-Codes festlegen — ohne den Umweg über den Editor."
spec_refs: ["Pflichtenheft §3.1.2", "Pflichtenheft §12.11 BUG-FIX-01"]
risk: low
complexity: low
dependencies: ["QrCreator.php (Backend bereits vollständig)"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: `resources/views/livewire/qr-creator.blade.php`
- Out of scope: Backend-Logik (bereits implementiert), Editor-Password-Flow

## Problem

`QrCreator.php` hat bereits:
- Property `public ?string $password = null;` (Line 51)
- Validation `'password' => ['nullable', 'string', 'min:4']` (Line 253)
- Payload-Übergabe `$payload['password'] = $validated['password']` (Line 301-302)

Aber die Blade-View zeigt nur einen Text-Hinweis anstatt eines Input-Feldes:
```
resources/views/livewire/qr-creator.blade.php:236-239
```

## Lösung

1. Den Platzhalter-Text durch ein Password-Input-Feld ersetzen (analog `qr-code-editor.blade.php:162-164`).
2. Feature-Gate beachten: nur Pro/Business (Property `canUsePasswordProtection`).
3. Feld im Advanced-Settings-Bereich platzieren, analog zu `maxScans` und `burn`.

### Implementation

In `qr-creator.blade.php`, ersetze den Block (Zeile ~236-239):

```blade
{{-- Vorher: Hinweis-Text --}}
{{-- Nachher: --}}
@if ($canUsePasswordProtection)
    <div class="sm:col-span-2">
        <x-input-label for="password" :value="__('Password protection (optional)')" />
        <x-text-input id="password" type="password" wire:model="password"
            class="mt-1 block w-full" placeholder="{{ __('Leave blank for no password') }}"
            autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
@else
    <div class="sm:col-span-2">
        <p class="rounded-md bg-gray-50 p-3 text-xs text-gray-500">
            🔒 <span class="font-semibold text-indigo-600">Pro</span>
            — {{ __('Password protection is available on Pro and Business plans.') }}
        </p>
    </div>
@endif
```

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| Creator zeigt Password-Feld für Pro/Business | Feature | `test_pro_user_sees_password_field_on_create` |
| Creator zeigt Upgrade-Hinweis für Free | Feature | `test_free_user_sees_password_upgrade_hint_on_create` |
| Passwort wird bei Erstellung gespeichert | Feature | `test_password_set_during_create_persists` |
| QR-Code mit Passwort erfordert Passwort bei Auflösung | Feature | bestehende resolver/password tests |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `blade`, `testing`; Candidate Role: Frontend Developer; Minimum/Preferred Tier: standard; Allowed Classes: low; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
