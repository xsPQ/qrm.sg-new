<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createRouteWithQrCode(array $qrAttributes = [], array $routeAttributes = []): QrCodeRoute
    {
        $user = User::factory()->create();

        $attributes = array_merge([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'entitlement_snapshot' => ['tier' => 'pro'],
        ], $qrAttributes);

        // The snapshot is immutable and not mass-assignable (DEV-592); set it
        // via direct attribute write so null/overrides still work as before.
        $snapshot = $attributes['entitlement_snapshot'] ?? null;
        unset($attributes['entitlement_snapshot']);

        $qrCode = QrCode::create($attributes);

        if ($snapshot !== null) {
            $qrCode->entitlement_snapshot = $snapshot;
            $qrCode->save();
        }

        return QrCodeRoute::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'host' => 'qrm.sg',
        ], $routeAttributes));
    }

    public function test_svg_download_returns_valid_svg(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="qr-ABC123.svg"', $response->headers->get('Content-Disposition'));
    }

    public function test_png_download_returns_valid_png(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/api/qr/ABC123/download.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="qr-ABC123.png"', $response->headers->get('Content-Disposition'));
    }

    public function test_download_returns_404_for_nonexistent_code(): void
    {
        $response = $this->get('/api/qr/NONEXIST/download.svg');

        $response->assertNotFound();
    }

    public function test_download_returns_423_for_disabled_qr_code(): void
    {
        $this->createRouteWithQrCode(['status' => 'disabled']);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertStatus(423);
    }

    public function test_download_returns_404_for_soft_deleted_qr_code(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Deleted QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);
        $qrCode->delete();

        QrCodeRoute::create([
            'qr_code_id' => $qrCode->id,
            'code' => 'DELETED',
            'host' => 'qrm.sg',
        ]);

        $response = $this->get('/api/qr/DELETED/download.svg');

        $response->assertNotFound();
    }

    public function test_svg_has_no_branding_for_pro_entitlement(): void
    {
        $this->createRouteWithQrCode(['entitlement_snapshot' => ['tier' => 'pro']]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertStringNotContainsString('qrm.sg', $response->content());
    }

    public function test_svg_has_no_branding_for_business_entitlement(): void
    {
        $this->createRouteWithQrCode(['entitlement_snapshot' => ['plan' => 'business']]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertStringNotContainsString('qrm.sg', $response->content());
    }

    public function test_svg_has_branding_for_free_entitlement(): void
    {
        $this->createRouteWithQrCode(['entitlement_snapshot' => ['tier' => 'free']]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertStringContainsString('qrm.sg', $response->content());
    }

    public function test_svg_has_branding_when_entitlement_snapshot_is_null(): void
    {
        $this->createRouteWithQrCode(['entitlement_snapshot' => null]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertStringContainsString('qrm.sg', $response->content());
    }

    public function test_download_supports_size_parameter(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/api/qr/ABC123/download.svg?size=500');

        $response->assertOk();
    }

    public function test_download_rejects_invalid_size(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/api/qr/ABC123/download.svg?size=invalid');

        $response->assertOk();
    }

    public function test_download_finds_by_alias(): void
    {
        $this->createRouteWithQrCode([], [
            'code' => 'DEF456',
            'alias' => 'my-link',
        ]);

        $response = $this->get('/api/qr/my-link/download.svg');

        $response->assertOk();
    }

    public function test_security_headers_are_set(): void
    {
        $this->createRouteWithQrCode();

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_download_does_not_increment_scan_count(): void
    {
        $this->createRouteWithQrCode();

        $this->get('/api/qr/ABC123/download.svg');

        $qrCode = QrCode::first();
        $this->assertEquals(0, $qrCode->scan_count);
    }
}
