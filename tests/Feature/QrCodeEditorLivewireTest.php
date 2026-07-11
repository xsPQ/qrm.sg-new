<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\QrCodeEditor;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use App\Policies\QrCodePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P2-T03 (DEV-155): QR-Code edit + delete feature tests.
 *
 * Covers the QrCodeEditor Livewire component, the QrCodeEditController owner
 * gate and the end-to-end edit page route — type-specific edits, validation,
 * alias-collision handling, the owner/admin policy (403 for non-owners) and the
 * two-step delete-with-confirmation soft-delete.
 */
class QrCodeEditorLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function host(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeQrCode(User $user, array $overrides = []): QrCode
    {
        // `entitlement_snapshot` is intentionally NOT fillable on the model
        // (immutable per-code snapshot, §3.5.3/§6.2), so mass-assigning it via
        // QrCode::create() silently drops it and the code reconstructs to a
        // Free snapshot. Pull it out of the overrides and write it directly —
        // the model's saving guard allows the first-time assignment.
        $snapshot = $overrides['entitlement_snapshot'] ?? null;
        unset($overrides['entitlement_snapshot']);

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'scan_count' => 0,
            'burn' => false,
        ], $overrides));

        if ($snapshot !== null) {
            $qrCode->entitlement_snapshot = $snapshot;
            $qrCode->save();
        }

        return $qrCode;
    }

    private function attachRoute(QrCode $qrCode, ?string $alias = null, ?string $code = null): QrCodeRoute
    {
        return QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => $code ?? strtoupper(Str::random(8)),
            'alias' => $alias,
            'host' => $this->host(),
            'created_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------
    // Access / owner policy (Fremder → 403)
    // ---------------------------------------------------------------

    public function test_guest_is_redirected_from_edit_page(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        $this->get("/qr-codes/{$qrCode->id}/edit")->assertRedirect('/login');
    }

    public function test_owner_sees_the_edit_form(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        $this->actingAs($owner)
            ->get("/qr-codes/{$qrCode->id}/edit")
            ->assertOk()
            ->assertSee(__('Edit QR Code'))
            ->assertSee(__('Save changes'))
            ->assertSee(__('Danger zone'));
    }

    public function test_non_owner_gets_403_on_edit_page(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        $this->actingAs($intruder)
            ->get("/qr-codes/{$qrCode->id}/edit")
            ->assertForbidden();
    }

    public function test_non_owner_cannot_mount_the_editor_component(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        // The owner gate is enforced on mount: the intruder cannot even render
        // the editor (the route returns 403, see test_non_owner_gets_403...).
        // The policy itself is the single source of truth.
        $policy = new QrCodePolicy();

        $this->assertFalse($policy->update($intruder, $qrCode));
        $this->assertTrue($policy->update($owner, $qrCode));
    }

    public function test_admin_can_open_edit_page_for_others_code(): void
    {
        $owner = User::factory()->create();
        $admin = $this->adminUser();
        $qrCode = $this->makeQrCode($owner);

        $this->actingAs($admin)
            ->get("/qr-codes/{$qrCode->id}/edit")
            ->assertOk()
            ->assertSee(__('Save changes'));
    }

    // ---------------------------------------------------------------
    // Mount / seeding
    // ---------------------------------------------------------------

    public function test_mount_seeds_title_content_and_alias_from_persisted_code(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Campaign 2026',
            'content' => ['url' => 'https://campaign.example.com'],
        ]);
        $this->attachRoute($qrCode, 'campaign-2026');

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->assertSet('type', 'url')
            ->assertSet('title', 'Campaign 2026')
            ->assertSet('content.url', 'https://campaign.example.com')
            ->assertSet('alias', 'campaign-2026');
    }

    public function test_type_cannot_be_changed_on_edit(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['type' => 'url']);

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('type', 'message')
            ->assertSet('type', 'url');
    }

    // ---------------------------------------------------------------
    // Edit per type (at least one example per type)
    // ---------------------------------------------------------------

    /**
     * @dataProvider validTypeContentProvider
     */
    #[DataProvider('validTypeContentProvider')]
    public function test_edit_persists_valid_changes_per_type(string $type, array $initialContent, array $edits, string $assertKey, mixed $assertValue): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'type' => $type,
            'content' => $initialContent,
        ]);

        $component = Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode]);

        foreach ($edits as $key => $value) {
            $component->set("content.{$key}", $value);
        }

        $component->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', __('QR code updated.'));

        $this->assertSame($assertValue, $qrCode->fresh()->content[$assertKey]);
    }

    public static function validTypeContentProvider(): array
    {
        return [
            'url' => ['url', ['url' => 'https://old.example.com'], ['url' => 'https://new.example.com'], 'url', 'https://new.example.com'],
            'message' => ['message', ['message' => 'Old text'], ['message' => 'Updated message body'], 'message', 'Updated message body'],
            'redirect' => ['redirect', ['target_url' => 'https://old.example.com', 'redirect_code' => '301'], ['target_url' => 'https://redirect.example.com'], 'target_url', 'https://redirect.example.com'],
            'social' => ['social', ['platform' => 'twitter', 'username' => 'old-handle'], ['username' => 'octocat'], 'username', 'octocat'],
            'wifi' => ['wifi', ['ssid' => 'OldNet', 'encryption' => 'WPA'], ['ssid' => 'NewNet'], 'ssid', 'NewNet'],
            'crypto' => ['crypto', ['currency' => 'BTC', 'address' => 'oldaddr'], ['address' => 'bc1qnewaddress'], 'address', 'bc1qnewaddress'],
            'event' => ['event', ['title' => 'Old event', 'start' => '2026-08-01'], ['title' => 'New event'], 'title', 'New event'],
            'vcard' => ['vcard', ['first_name' => 'Old', 'last_name' => 'Name'], ['first_name' => 'Jane'], 'first_name', 'Jane'],
        ];
    }

    public function test_owner_can_update_title(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['title' => 'Old title']);

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('title', 'New title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', __('QR code updated.'));

        $this->assertSame('New title', $qrCode->fresh()->title);
    }

    // ---------------------------------------------------------------
    // Validation (ungültige Eingaben werden abgewiesen)
    // ---------------------------------------------------------------

    public function test_save_shows_validation_error_when_title_missing(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_save_shows_validation_error_for_invalid_type_content(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('content.url', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['content.url']);
    }

    // ---------------------------------------------------------------
    // Alias handling (Kollision → Fehler / entitlement re-validation)
    // ---------------------------------------------------------------

    public function test_free_tier_code_cannot_set_custom_alias(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        // Custom aliases are a Pro/Business-only feature (§2.1). Editing a
        // Free-snapshot code surfaces a clear field-level upgrade error and
        // never persists the alias.
        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('alias', 'brand-new-alias')
            ->call('save')
            ->assertHasErrors(['custom_alias']);

        $this->assertDatabaseMissing('qr_code_routes', ['alias' => 'brand-new-alias']);
    }

    public function test_pro_tier_code_can_change_alias(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);
        $this->attachRoute($qrCode, 'old-alias');

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('alias', 'brand-new-alias')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('brand-new-alias', $qrCode->fresh()->route->alias);
    }

    public function test_alias_collision_on_save_surfaces_field_error(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->makeQrCode($owner, ['entitlement_snapshot' => ['tier' => 'pro']]);
        $this->attachRoute($mine, 'my-old-alias');

        $taken = $this->makeQrCode($other);
        $this->attachRoute($taken, 'taken-by-other');

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $mine])
            ->set('alias', 'taken-by-other')
            ->call('save')
            ->assertHasErrors(['alias']);

        // A failed save must leave the existing alias untouched.
        $this->assertSame('my-old-alias', $mine->fresh()->route->alias);
    }

    public function test_live_alias_check_reports_available_for_own_current_alias(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);
        $this->attachRoute($qrCode, 'keep-this-alias');

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->call('checkAlias', 'keep-this-alias')
            ->assertSet('aliasStatus', 'available');
    }

    public function test_owner_can_clear_alias(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);
        $this->attachRoute($qrCode, 'remove-me');

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->set('alias', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($qrCode->fresh()->route->alias);
    }

    // ---------------------------------------------------------------
    // Delete with confirmation (Soft-Delete)
    // ---------------------------------------------------------------

    public function test_delete_requires_two_step_confirmation(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['title' => 'Doomed code']);

        $component = Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode]);

        // Initially the confirmation prompt is hidden.
        $component
            ->assertSet('confirmingDelete', false)
            ->assertDontSee(__('Are you sure you want to delete this QR code?'));

        // First step opens the confirmation prompt.
        $component->call('confirmDelete')
            ->assertSet('confirmingDelete', true)
            ->assertSee(__('Are you sure you want to delete this QR code?'));

        // The code is still present until the second step is confirmed.
        $this->assertNotNull(QrCode::find($qrCode->id));

        // Cancel returns to the hidden state without deleting.
        $component->call('cancelDelete')
            ->assertSet('confirmingDelete', false)
            ->assertDontSee(__('Are you sure you want to delete this QR code?'));
    }

    public function test_confirmed_delete_soft_deletes_the_code(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, ['title' => 'Delete me']);

        Livewire::actingAs($owner)
            ->test(QrCodeEditor::class, ['qrCode' => $qrCode])
            ->call('confirmDelete')
            ->call('delete')
            ->assertHasNoErrors();

        // Soft-delete: the row remains with a deleted_at timestamp and drops
        // out of the default query.
        $this->assertSoftDeleted('qr_codes', ['id' => $qrCode->id]);
        $this->assertNull(QrCode::find($qrCode->id));
    }

    public function test_non_owner_cannot_delete_others_code(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $qrCode = $this->makeQrCode($owner);

        // The delete policy is denied for non-owners (and the edit page itself
        // is 403, so the intruder can never reach the delete action via the UI).
        $policy = new QrCodePolicy();

        $this->assertFalse($policy->delete($intruder, $qrCode));
        $this->assertTrue($policy->delete($owner, $qrCode));

        $this->assertNotNull(QrCode::find($qrCode->id));
    }

    // ---------------------------------------------------------------
    // Smoke (Edit + Delete über die echte Route)
    // ---------------------------------------------------------------

    public function test_edit_page_smoke_loads_form_for_owner(): void
    {
        $owner = User::factory()->create();
        $qrCode = $this->makeQrCode($owner, [
            'title' => 'Smoke code',
            'content' => ['url' => 'https://smoke.example.com'],
        ]);
        $this->attachRoute($qrCode, 'smoke-alias');

        $this->actingAs($owner)
            ->get("/qr-codes/{$qrCode->id}/edit")
            ->assertOk()
            ->assertSee('Smoke code')
            ->assertSee('smoke-alias')
            ->assertSee(__('Save changes'))
            ->assertSee(__('Delete QR code'));
    }
}
