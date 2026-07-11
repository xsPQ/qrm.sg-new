<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodePublicResolverTest extends TestCase
{
    use RefreshDatabase;

    private function createRouteWithQrCode(array $qrAttributes = [], array $routeAttributes = []): QrCodeRoute
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ], $qrAttributes));

        return QrCodeRoute::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'host' => 'localhost',
        ], $routeAttributes));
    }

    public function test_public_resolver_redirects_active_url_qr(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/ABC123');

        $response->assertRedirect('https://example.com');
    }

    public function test_public_resolver_returns_404_for_unknown_code(): void
    {
        $response = $this->get('/NONEXIST');

        $response->assertNotFound();
        $response->assertViewIs('qr-types.error');
    }

    public function test_public_resolver_lookup_is_case_insensitive(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/abc123');

        $response->assertRedirect('https://example.com');
    }

    public function test_public_resolver_resolves_by_alias(): void
    {
        $this->createRouteWithQrCode([], [
            'code' => 'XYZ789',
            'alias' => 'my-link',
        ]);

        $response = $this->get('/my-link');

        $response->assertRedirect('https://example.com');
    }

    public function test_public_resolver_filters_by_host(): void
    {
        $this->createRouteWithQrCode([], [
            'host' => 'other.example',
        ]);

        $response = $this->get('/ABC123');

        $response->assertNotFound();
    }

    public function test_public_resolver_returns_410_for_expired_qr(): void
    {
        $this->createRouteWithQrCode([
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(410);
        $response->assertViewIs('qr-types.error');
    }

    public function test_public_resolver_returns_423_for_disabled_qr(): void
    {
        $this->createRouteWithQrCode([
            'status' => 'disabled',
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(423);
    }

    public function test_public_resolver_returns_410_for_burned_qr(): void
    {
        $this->createRouteWithQrCode([
            'status' => 'burned',
            'burn' => true,
            'scan_count' => 1,
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(410);
    }

    public function test_public_resolver_returns_410_when_max_scans_reached(): void
    {
        $this->createRouteWithQrCode([
            'max_scans' => 3,
            'scan_count' => 3,
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(410);
    }

    public function test_public_resolver_returns_404_for_soft_deleted_qr(): void
    {
        $route = $this->createRouteWithQrCode();
        $route->qrCode()->delete();

        $response = $this->get('/ABC123');

        $response->assertNotFound();
    }

    public function test_public_resolver_applies_security_headers_on_redirect(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/ABC123');

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_resolver_applies_security_headers_on_error_page(): void
    {
        $response = $this->get('/NONEXIST');

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_resolver_honors_redirect_code(): void
    {
        $this->createRouteWithQrCode([
            'content' => ['url' => 'https://example.com', 'redirect_code' => 301],
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(301);
        $response->assertRedirect('https://example.com');
    }

    public function test_public_resolver_falls_back_to_302_for_invalid_redirect_code(): void
    {
        $this->createRouteWithQrCode([
            'content' => ['url' => 'https://example.com', 'redirect_code' => 418],
        ]);

        $response = $this->get('/ABC123');

        $response->assertStatus(302);
    }

    public function test_public_resolver_increments_scan_count_and_logs_scan(): void
    {
        $route = $this->createRouteWithQrCode();

        $this->get('/ABC123');

        $qrCode = $route->fresh()->qrCode;
        $this->assertEquals(1, $qrCode->scan_count);
        $this->assertDatabaseCount('scans', 1);

        $scan = Scan::first();
        $this->assertNotNull($scan);
        $this->assertNotEquals('127.0.0.1', $scan->ip_hash);
        $this->assertEquals(64, strlen($scan->ip_hash));
    }

    public function test_public_resolver_serves_message_type_as_html(): void
    {
        $this->createRouteWithQrCode([
            'type' => 'message',
            'content' => ['title' => 'Hello', 'body' => 'World'],
        ]);

        $response = $this->get('/ABC123');

        $response->assertOk();
        $response->assertViewIs('qr-types.message');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_resolver_serves_contact_alias_type_as_html(): void
    {
        $this->createRouteWithQrCode([
            'type' => 'contact',
            'content' => ['firstName' => 'Max', 'lastName' => 'Mustermann', 'phone' => '+49 123 456789', 'email' => 'max@example.com'],
        ]);

        $response = $this->get('/ABC123');

        $response->assertOk();
        $response->assertViewIs('qr-types.vcard');
        $response->assertSee('Max Mustermann');
        $response->assertSee('max@example.com');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
