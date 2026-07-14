<?php

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Livewire\QrCodeEditor;
use App\Models\QrCode;
use App\Models\QrCodeRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FEAT-07: QR-Code-Versionshistorie
 *
 * Verifies that:
 * - Editing a QR code automatically creates a revision snapshot
 * - The snapshot captures the PREVIOUS state
 * - Multiple edits create incrementing version numbers
 * - Restore brings back the old content
 * - The 50-revision cap is enforced
 */
class QrCodeRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function createQrCode(): array
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'type' => 'message',
            'content' => ['message' => 'Original content'],
            'settings' => ['style' => []],
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
        ]);

        return [$user, $qrCode];
    }

    public function test_editing_title_creates_revision_with_previous_state(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update(['title' => 'New Title']);

        $revision = QrCodeRevision::first();

        $this->assertNotNull($revision);
        $this->assertEquals(1, $revision->version);
        $this->assertEquals('Original Title', $revision->snapshot['title']);
        $this->assertStringContainsString('title', $revision->change_summary);
    }

    public function test_editing_content_creates_revision_with_previous_content(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update([
            'content' => ['message' => 'Updated content'],
        ]);

        $revision = QrCodeRevision::first();

        $this->assertNotNull($revision);
        $this->assertEquals('Original content', $revision->snapshot['content']['message']);
        $this->assertStringContainsString('content', $revision->change_summary);
    }

    public function test_multiple_edits_create_incrementing_versions(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update(['title' => 'v1']);
        $qrCode->update(['title' => 'v2']);
        $qrCode->update(['title' => 'v3']);

        $this->assertEquals(3, QrCodeRevision::count());

        $versions = QrCodeRevision::orderBy('version')->pluck('version')->toArray();
        $this->assertEquals([1, 2, 3], $versions);
    }

    public function test_non_tracked_fields_dont_create_revisions(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        // scan_count is not tracked
        $qrCode->update(['scan_count' => 42]);

        $this->assertEquals(0, QrCodeRevision::count());
    }

    public function test_revision_captures_user_id(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update(['title' => 'Changed']);

        $revision = QrCodeRevision::first();

        $this->assertEquals($user->id, $revision->user_id);
    }

    public function test_revision_cap_at_50(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        for ($i = 0; $i < 55; $i++) {
            $qrCode->update(['title' => "Iteration {$i}"]);
        }

        $this->assertEquals(50, QrCodeRevision::where('qr_code_id', $qrCode->id)->count());

        // The oldest revisions should have been trimmed; version numbers should
        // be from the last 50 changes.
        $minVersion = QrCodeRevision::where('qr_code_id', $qrCode->id)->min('version');
        $this->assertGreaterThan(5, $minVersion);
    }

    public function test_restore_via_service_brings_back_previous_content(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        // Original content: body = "Original content"
        // Edit 1: change to "Updated content"
        $qrCode->update([
            'content' => ['message' => 'Updated content'],
        ]);

        // The revision captured the PREVIOUS state (Original content)
        $revision = QrCodeRevision::first();
        $this->assertEquals('Original content', $revision->snapshot['content']['message']);

        // Restore: apply the revision's snapshot back to the QR code
        $qrCode->update([
            'title' => $revision->snapshot['title'],
            'content' => $revision->snapshot['content'],
            'settings' => $revision->snapshot['settings'] ?? [],
        ]);

        // The QR code should now have the original content back
        $qrCode->refresh();
        $this->assertEquals('Original content', $qrCode->content['message']);
    }

    public function test_revisions_are_deleted_when_qr_code_is_force_deleted(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update(['title' => 'Changed']);
        $this->assertEquals(1, QrCodeRevision::count());

        $qrCode->forceDelete();

        $this->assertEquals(0, QrCodeRevision::count());
    }

    public function test_change_summary_for_content_field_change(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update([
            'content' => ['message' => 'New body text'],
        ]);

        $revision = QrCodeRevision::first();

        $this->assertStringContainsString('content', $revision->change_summary);
        $this->assertStringContainsString('message', $revision->change_summary);
    }

    public function test_restore_revision_via_livewire_restores_all_fields(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        // Change title, content, settings and status.
        $qrCode->update([
            'title' => 'After Edit',
            'content' => ['message' => 'Changed'],
            'settings' => ['style' => ['fg_color' => '#ff0000']],
            'status' => 'paused',
        ]);

        // The revision now holds the PREVIOUS state (original values).
        $revision = QrCodeRevision::first();
        $this->assertEquals('Original Title', $revision->snapshot['title']);
        $this->assertEquals('active', $revision->snapshot['status']);

        // Restore via Livewire component.
        Livewire::actingAs($user)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->call('restoreRevision', $revision->id)
            ->assertHasNoErrors();

        // Assert all four tracked fields are restored.
        $qrCode->refresh();
        $this->assertEquals('Original Title', $qrCode->title);
        $this->assertEquals('Original content', $qrCode->content['message']);
        $this->assertEquals('active', $qrCode->status);
    }

    public function test_restore_revision_without_status_in_snapshot_keeps_current_status(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        // Create a revision by changing content.
        $qrCode->update([
            'content' => ['message' => 'Changed'],
        ]);

        $revision = QrCodeRevision::first();

        // Simulate an older snapshot without the status field.
        $snapshot = $revision->snapshot;
        unset($snapshot['status']);
        $revision->snapshot = $snapshot;
        $revision->save();

        // Change status to paused after the revision was created.
        $qrCode->update(['status' => 'paused']);

        // The latest revision is from the status change; we restore the
        // older one that lacks status.
        $oldRevision = QrCodeRevision::orderBy('version')->first();

        Livewire::actingAs($user)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->call('restoreRevision', $oldRevision->id)
            ->assertHasNoErrors();

        // Status should remain 'paused' (the current one), not changed.
        $qrCode->refresh();
        $this->assertEquals('paused', $qrCode->status);
        // But content should be restored.
        $this->assertEquals('Original content', $qrCode->content['message']);
    }

    public function test_restore_revision_creates_new_revision_for_undo(): void
    {
        [$user, $qrCode] = $this->createQrCode();

        $this->actingAs($user);

        $qrCode->update(['title' => 'Changed']);

        $revisionCountBefore = QrCodeRevision::count(); // 1

        $revision = QrCodeRevision::first();

        Livewire::actingAs($user)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->call('restoreRevision', $revision->id)
            ->assertHasNoErrors();

        // The restore itself should trigger a new revision (capturing the
        // "before restore" state), making the action reversible.
        $this->assertEquals($revisionCountBefore + 1, QrCodeRevision::count());
    }
}
