# Task BUG-FIX-02: Revision-Restore vollständig und getestet

```yaml
phase: P3
workflow_state: task_review
goal: "restoreRevision() stellt alle snapshot-tracked Felder wieder her (inkl. status) und ist durch einen Livewire-Integrationstest abgesichert."
spec_refs: ["Pflichtenheft §12.11 BUG-FIX-02", "FEAT-07 Version History"]
risk: medium
complexity: low
dependencies: ["QrCodeRevisionObserver", "QrCodeEditor::restoreRevision()"]
attempt_counter: 0
failure_reason: null
current_owner: null
next_allowed_state: ready
escalation_target: Task Reviewer
```

## Scope

- Create/Modify: `app/Livewire/QrCodeEditor.php` (`restoreRevision`), `tests/Feature/QrCodeRevisionTest.php`
- Out of scope: Observer-Logik (fehlerfrei), UI-Template

## Problem 1: Status wird nicht wiederhergestellt

Der Observer speichert `status` im Snapshot:
```php
private const TRACKABLE = ['title', 'content', 'settings', 'status'];
```

Aber `restoreRevision()` (Line 520-540) ignoriert `status`:
```php
$payload = [
    'title' => $snapshot['title'] ?? $this->qrCode->title,
    'content' => $snapshot['content'] ?? $this->qrCode->content,
    'settings' => $snapshot['settings'] ?? $this->qrCode->settings,
    // status fehlt!
];
```

### Fix

```php
$payload = [
    'title' => $snapshot['title'] ?? $this->qrCode->title,
    'content' => $snapshot['content'] ?? $this->qrCode->content,
    'settings' => $snapshot['settings'] ?? $this->qrCode->settings,
];

if (array_key_exists('status', $snapshot)) {
    $payload['status'] = $snapshot['status'];
}
```

`array_key_exists` statt `??` weil `status` ein gültiger String-Wert ist (kein null-Edge-Case).

## Problem 2: Kein Livewire-Integrationstest

Der einzige Restore-Test (`test_restore_via_service_brings_back_previous_content`, Line 135) macht direkte `$qrCode->update()` Aufrufe und testet nicht `restoreRevision()`.

### Fix

Neuer Test in `tests/Feature/QrCodeRevisionTest.php`:

```php
public function test_restore_revision_via_livewire_restores_all_fields(): void
{
    [$user, $qrCode] = $this->createQrCode();

    // Change title, content, settings and status
    $qrCode->update([
        'title' => 'After Edit',
        'content' => ['title' => 'Msg', 'body' => 'Changed'],
        'settings' => ['style' => ['fg_color' => '#ff0000']],
        'status' => 'paused',
    ]);

    // The revision now holds the PREVIOUS state
    $revision = QrCodeRevision::first();

    // Act: restore via Livewire
    Livewire::actingAs($user)
        ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
        ->call('restoreRevision', $revision->id)
        ->assertHasNoErrors();

    // Assert all 4 fields restored
    $qrCode->refresh();
    $this->assertEquals('Original Title', $qrCode->title);
    $this->assertEquals('Original content', $qrCode->content['body']);
    $this->assertEquals('active', $qrCode->status);
}

public function test_restore_revision_without_status_in_snapshot_keeps_current_status(): void
{
    // Edge case: older revision without status field
    // Status should remain unchanged
}
```

## Akzeptanz und Tests

| Kriterium | Testname/Typ | Nachweis |
|---|---|---|
| restoreRevision stellt title, content, settings, status wieder her | Feature | `test_restore_revision_via_livewire_restores_all_fields` |
| Snapshot ohne status → Status unverändert | Edge Case | `test_restore_revision_without_status_in_snapshot_keeps_current_status` |
| Restore erzeugt neue Revision (undo möglich) | Feature | bestehende Logik + assertion in neuem Test |

## Routingrahmen

Required Skills: `laravel`, `livewire`, `testing`; Candidate Role: Backend Domain Developer; Minimum/Preferred Tier: standard; Allowed Classes: low; Max Cost: low; Premium Allowed/Reason: false; Fallback: Task Reviewer.
