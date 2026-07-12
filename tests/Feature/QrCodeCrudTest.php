<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrCodeCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['plan' => 'business']);
        Sanctum::actingAs($this->user);
    }

    public function test_index_returns_empty_list(): void
    {
        $response = $this->getJson('/api/qr-codes');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(0, 'data');
    }

    public function test_index_returns_qr_codes(): void
    {
        QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->getJson('/api/qr-codes');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test QR');
    }

    public function test_index_filters_by_type(): void
    {
        QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'URL QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Message QR',
            'type' => 'message',
            'content' => ['message' => 'Hello'],
        ]);

        $response = $this->getJson('/api/qr-codes?type=message');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Message QR');
    }

    public function test_index_only_returns_own_qr_codes(): void
    {
        $otherUser = User::factory()->create();
        QrCode::create([
            'user_id' => $otherUser->id,
            'title' => 'Other QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->getJson('/api/qr-codes');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_store_creates_url_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'My URL',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My URL')
            ->assertJsonPath('data.type', 'url')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.content.url', 'https://example.com')
            ->assertJsonStructure(['data' => ['route' => ['code', 'host']]]);

        $this->assertDatabaseHas('qr_codes', ['title' => 'My URL', 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('qr_code_routes', ['qr_code_id' => $response->json('data.id')]);
    }

    public function test_store_creates_message_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'My Message',
            'type' => 'message',
            'content' => ['message' => 'Hello World'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content.message', 'Hello World');
    }

    public function test_store_creates_redirect_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'My Redirect',
            'type' => 'redirect',
            'content' => [
                'target_url' => 'https://example.com',
                'redirect_code' => '301',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content.target_url', 'https://example.com')
            ->assertJsonPath('data.content.redirect_code', '301');
    }

    public function test_store_creates_social_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'My Social',
            'type' => 'social',
            'content' => [
                'platform' => 'github',
                'username' => 'octocat',
            ],
        ]);

        $response->assertCreated();
    }

    public function test_store_creates_wifi_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Office WiFi',
            'type' => 'wifi',
            'content' => [
                'ssid' => 'OfficeNet',
                'encryption' => 'WPA2',
                'password' => 'secret123',
            ],
        ]);

        $response->assertCreated();
    }

    public function test_store_creates_crypto_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'BTC Address',
            'type' => 'crypto',
            'content' => [
                'currency' => 'BTC',
                'address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            ],
        ]);

        $response->assertCreated();
    }

    public function test_store_creates_event_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Conference',
            'type' => 'event',
            'content' => [
                'title' => 'Laracon',
                'start' => '2026-12-01T09:00:00Z',
            ],
        ]);

        $response->assertCreated();
    }

    public function test_store_creates_contact_qr_code(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'John Doe',
            'type' => 'vcard',
            'content' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ],
        ]);

        $response->assertCreated();
    }

    public function test_store_rejects_invalid_content_for_type(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Bad URL',
            'type' => 'url',
            'content' => ['url' => 'not-a-url'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content.url']);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Test',
            'type' => 'invalid',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_creates_route_with_alias(): void
    {
        // Custom aliases are a Pro-gated capability (§2.1); grant an active
        // Pro subscription so the entitlement gate accepts the alias.
        Subscription::create([
            'user_id' => $this->user->id,
            'stripe_id' => 'cus_pro_alias',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Alias QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'alias' => 'my-cool-link',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.route.alias', 'my-cool-link');
    }

    public function test_show_returns_qr_code(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Show Me',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->getJson("/api/qr-codes/{$qrCode->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $qrCode->id)
            ->assertJsonPath('data.title', 'Show Me');
    }

    public function test_show_returns_403_for_other_users_qr_code(): void
    {
        $otherUser = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $otherUser->id,
            'title' => 'Not Yours',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->getJson("/api/qr-codes/{$qrCode->id}");

        $response->assertForbidden();
    }

    public function test_update_updates_title(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Original',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->putJson("/api/qr-codes/{$qrCode->id}", [
            'title' => 'Updated',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_update_updates_content(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->putJson("/api/qr-codes/{$qrCode->id}", [
            'content' => ['url' => 'https://updated.example.com'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.content.url', 'https://updated.example.com');
    }

    public function test_update_rejects_invalid_content_for_existing_type(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->putJson("/api/qr-codes/{$qrCode->id}", [
            'content' => ['url' => 'not-a-valid-url'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content.url']);
    }

    public function test_update_cannot_change_other_users_qr(): void
    {
        $otherUser = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $otherUser->id,
            'title' => 'Not Yours',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->putJson("/api/qr-codes/{$qrCode->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_deletes_qr_code(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id,
            'title' => 'Delete Me',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->deleteJson("/api/qr-codes/{$qrCode->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('qr_codes', ['id' => $qrCode->id]);
    }

    public function test_destroy_cannot_delete_other_users_qr(): void
    {
        $otherUser = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $otherUser->id,
            'title' => 'Not Yours',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response = $this->deleteJson("/api/qr-codes/{$qrCode->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/qr-codes');

        $response->assertUnauthorized();
    }

    public function test_creates_qr_code_route_automatically(): void
    {
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Routed QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.route.code'));
    }

    public function test_store_rejects_free_user_over_limit_with_upgrade_hint(): void
    {
        config(['qr.free.max_active_qr_codes' => 2]);

        $this->postJson('/api/qr-codes', [
            'title' => 'First', 'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ])->assertCreated();
        $this->postJson('/api/qr-codes', [
            'title' => 'Second', 'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ])->assertCreated();

        // 11th-equivalent: over the configured limit.
        $response = $this->postJson('/api/qr-codes', [
            'title' => 'Third', 'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertPaymentRequired()
            ->assertJsonPath('error', 'free_tier_limit_exceeded')
            ->assertJsonPath('upgrade_required', true)
            ->assertJsonPath('limit', 2)
            ->assertJsonPath('active_count', 2)
            ->assertJsonStructure(['message', 'error', 'upgrade_required', 'active_count', 'limit', 'upgrade_hint']);
    }

    public function test_free_tier_status_endpoint_reports_remaining_headroom(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id, 'title' => 'Active', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);
        $qrCode->entitlement_snapshot = ['tier' => 'free'];
        $qrCode->save();

        $response = $this->getJson('/api/qr-codes/free-tier-status');

        $response->assertOk()
            ->assertJsonPath('tier', 'free')
            ->assertJsonPath('active_count', 1)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('remaining', 9)
            ->assertJsonPath('limit_reached', false);
    }

    public function test_show_resource_exposes_expiring_soon_flag(): void
    {
        $qrCode = QrCode::create([
            'user_id' => $this->user->id, 'title' => 'Expiring', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(2),
        ]);
        $qrCode->entitlement_snapshot = ['tier' => 'free'];
        $qrCode->save();

        $response = $this->getJson("/api/qr-codes/{$qrCode->id}");

        $response->assertOk()
            ->assertJsonPath('data.is_expiring_soon', true)
            ->assertJsonStructure(['data' => ['is_expiring_soon', 'expires_in_days']]);
    }
}
