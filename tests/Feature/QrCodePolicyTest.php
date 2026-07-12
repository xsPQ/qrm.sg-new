<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QrCodePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create(['plan' => 'business']);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_owner_can_view_own_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Mine',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/qr-codes/{$qrCode->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $qrCode->id);
    }

    public function test_owner_can_update_own_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Mine',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($owner);

        $this->putJson("/api/qr-codes/{$qrCode->id}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_owner_can_delete_own_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Mine',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/qr-codes/{$qrCode->id}")
            ->assertNoContent();
    }

    public function test_non_owner_cannot_view_others_qr_code_and_gets_403(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $intruder = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Private',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/qr-codes/{$qrCode->id}")->assertForbidden();
    }

    public function test_non_owner_cannot_update_others_qr_code_and_gets_403(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $intruder = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Private',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($intruder);

        $this->putJson("/api/qr-codes/{$qrCode->id}", ['title' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Private', $qrCode->fresh()->title);
    }

    public function test_non_owner_cannot_delete_others_qr_code_and_gets_403(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $intruder = User::factory()->create(['plan' => 'business']);
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Private',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($intruder);

        $this->deleteJson("/api/qr-codes/{$qrCode->id}")->assertForbidden();

        $this->assertNotNull($qrCode->fresh());
    }

    public function test_admin_can_view_any_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $admin = $this->adminUser();
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Owner Only',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/qr-codes/{$qrCode->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $qrCode->id);
    }

    public function test_admin_can_update_any_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $admin = $this->adminUser();
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Owner Only',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/qr-codes/{$qrCode->id}", ['title' => 'Admin Edit'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Admin Edit');
    }

    public function test_admin_can_delete_any_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $admin = $this->adminUser();
        $qrCode = QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Owner Only',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/qr-codes/{$qrCode->id}")->assertNoContent();
    }

    public function test_admin_index_lists_all_qr_codes(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $admin = $this->adminUser();

        QrCode::create([
            'user_id' => $owner->id,
            'title' => 'Owner QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        QrCode::create([
            'user_id' => $admin->id,
            'title' => 'Admin QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/qr-codes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_nonexistent_qr_code_returns_404(): void
    {
        $user = User::factory()->create(['plan' => 'business']);
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes/999999')->assertNotFound();
    }

    public function test_any_authenticated_user_can_create_qr_code(): void
    {
        $user = User::factory()->create(['plan' => 'business']);
        Sanctum::actingAs($user);

        $this->postJson('/api/qr-codes', [
            'title' => 'New',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ])->assertCreated();
    }
}
