<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entitlement\EntitlementGate;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QrCodeService(new QrCodeRouteService(), new EntitlementGate());
        $this->user = User::factory()->create();
    }

    private function grantPro(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'stripe_id' => 'cus_pro_1',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ]);
    }

    public function test_create_creates_qr_code_with_route(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertInstanceOf(QrCode::class, $qrCode);
        $this->assertEquals('Test QR', $qrCode->title);
        $this->assertEquals('url', $qrCode->type);
        $this->assertEquals('active', $qrCode->status);
        $this->assertNotNull($qrCode->route);
        $this->assertNotNull($qrCode->route->code);
    }

    public function test_create_creates_qr_code_with_alias(): void
    {
        $this->grantPro();

        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'alias' => 'my-test-link',
        ]);

        $this->assertEquals('my-test-link', $qrCode->route->alias);
    }

    public function test_create_stores_password_hash(): void
    {
        $this->grantPro();

        $qrCode = $this->service->create($this->user, [
            'title' => 'Protected QR',
            'type' => 'message',
            'content' => ['message' => 'Secret'],
            'password' => 'secret123',
        ]);

        $this->assertNotNull($qrCode->password_hash);
        $this->assertTrue(password_verify('secret123', $qrCode->password_hash));
    }

    public function test_create_stores_optional_fields(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Advanced QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'burn' => true,
            'max_scans' => 10,
            'expires_at' => now()->addDays(30),
            'settings' => ['theme' => 'dark'],
        ]);

        $this->assertTrue($qrCode->burn);
        $this->assertEquals(10, $qrCode->max_scans);
        $this->assertNotNull($qrCode->expires_at);
        $this->assertEquals('dark', $qrCode->settings['theme']);
    }

    public function test_create_sets_entitlement_snapshot(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertIsArray($qrCode->entitlement_snapshot);
        $this->assertEquals('free', $qrCode->entitlement_snapshot['tier']);
    }

    public function test_update_updates_title(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Original',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $updated = $this->service->update($qrCode, ['title' => 'Updated']);

        $this->assertEquals('Updated', $updated->title);
    }

    public function test_update_updates_content(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $updated = $this->service->update($qrCode, [
            'content' => ['url' => 'https://newexample.com'],
        ]);

        $this->assertEquals('https://newexample.com', $updated->content['url']);
    }

    public function test_update_updates_status(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $updated = $this->service->update($qrCode, ['status' => 'disabled']);

        $this->assertEquals('disabled', $updated->status);
    }

    public function test_update_adds_alias_to_existing_route(): void
    {
        $this->grantPro();

        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        $this->assertNull($qrCode->route->alias);

        $updated = $this->service->update($qrCode, ['alias' => 'cool-link']);

        $this->assertEquals('cool-link', $updated->route->alias);
    }

    public function test_update_removes_alias_when_null(): void
    {
        $this->grantPro();

        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'alias' => 'temp-link',
        ]);
        $this->assertEquals('temp-link', $qrCode->route->alias);

        $updated = $this->service->update($qrCode, ['alias' => null]);

        $this->assertNull($updated->route->alias);
    }

    public function test_update_clears_password_when_null(): void
    {
        $this->grantPro();

        $qrCode = $this->service->create($this->user, [
            'title' => 'Protected QR',
            'type' => 'message',
            'content' => ['message' => 'Secret'],
            'password' => 'secret123',
        ]);
        $this->assertNotNull($qrCode->password_hash);

        $updated = $this->service->update($qrCode, ['password' => null]);

        $this->assertNull($updated->password_hash);
    }

    public function test_delete_soft_deletes_qr_code(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->service->delete($qrCode);

        $this->assertSoftDeleted($qrCode);
    }

    public function test_list_for_user_returns_paginated_results(): void
    {
        $this->service->create($this->user, [
            'title' => 'QR 1',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        $this->service->create($this->user, [
            'title' => 'QR 2',
            'type' => 'message',
            'content' => ['message' => 'Hello'],
        ]);

        $result = $this->service->listForUser($this->user);

        $this->assertCount(2, $result->items());
    }

    public function test_list_for_user_filters_by_type(): void
    {
        $this->service->create($this->user, [
            'title' => 'URL QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
        $this->service->create($this->user, [
            'title' => 'Message QR',
            'type' => 'message',
            'content' => ['message' => 'Hello'],
        ]);

        $result = $this->service->listForUser($this->user, ['type' => 'url']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('URL QR', $result->items()[0]->title);
    }

    public function test_find_for_user_returns_qr_code(): void
    {
        $qrCode = $this->service->create($this->user, [
            'title' => 'Find Me',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $found = $this->service->findForUser($this->user, $qrCode->id);

        $this->assertEquals($qrCode->id, $found->id);
    }

    public function test_find_for_user_throws_for_other_user(): void
    {
        $otherUser = User::factory()->create();
        $qrCode = $this->service->create($this->user, [
            'title' => 'Mine',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->findForUser($otherUser, $qrCode->id);
    }
}
