<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrScanControllerTest extends TestCase
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
            'host' => 'example.com',
        ], $routeAttributes));
    }

    public function test_resolve_endpoint_redirects_for_active_url_qr(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/r/ABC123');

        $response->assertRedirect();
    }

    public function test_resolve_endpoint_returns_error_for_nonexistent_code(): void
    {
        $response = $this->get('/r/NONEXIST');

        $response->assertStatus(200);
        $response->assertViewIs('qr-types.error');
    }

    public function test_resolve_endpoint_finds_by_alias(): void
    {
        $this->createRouteWithQrCode([], [
            'code' => 'DEF456',
            'alias' => 'my-link',
        ]);

        $response = $this->get('/r/my-link');

        $response->assertRedirect();
    }

    public function test_resolve_endpoint_increments_scan_count(): void
    {
        $this->createRouteWithQrCode();

        $this->get('/r/ABC123');

        $qrCode = QrCode::first();
        $this->assertEquals(1, $qrCode->scan_count);
    }

    public function test_resolve_endpoint_creates_scan_record(): void
    {
        $this->createRouteWithQrCode();

        $this->get('/r/ABC123');

        $this->assertDatabaseCount('scans', 1);
    }

    public function test_password_endpoint_redirects_to_resolve_on_success(): void
    {
        $this->createRouteWithQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ], [
            'code' => 'SECURE1',
        ]);

        $response = $this->post('/r/SECURE1/password', [
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('qr.resolve', 'SECURE1'));
        $response->assertCookieNotExpired('qr_password_1');
    }

    public function test_password_endpoint_returns_error_for_wrong_password(): void
    {
        $this->createRouteWithQrCode([
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ], [
            'code' => 'SECURE1',
        ]);

        $response = $this->post('/r/SECURE1/password', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_endpoint_returns_error_for_nonexistent_code(): void
    {
        $response = $this->post('/r/NONEXIST/password', [
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_password_endpoint_redirects_when_qr_has_no_password(): void
    {
        $this->createRouteWithQrCode([], ['code' => 'NOPASS1']);

        $response = $this->post('/r/NOPASS1/password', [
            'password' => 'anything',
        ]);

        $response->assertRedirect(route('qr.resolve', 'NOPASS1'));
    }

    public function test_password_endpoint_requires_password_field(): void
    {
        $this->createRouteWithQrCode([], ['code' => 'VALIDATE1']);

        $response = $this->post('/r/VALIDATE1/password', []);

        $response->assertSessionHasErrors('password');
    }

    public function test_resolve_endpoint_returns_error_for_soft_deleted_qr(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Deleted QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);
        $qrCode->delete();

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'DELETED1',
            'host' => 'example.com',
        ]);

        $response = $this->get('/r/DELETED1');

        $response->assertViewIs('qr-types.error');
    }
}
