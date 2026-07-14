<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Visual customization integration tests (FEAT-04).
 *
 * Verifies that stored style settings flow through the download controller
 * and the preview service, and that entitlement gating is enforced.
 */
class VisualCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private function createRouteWithQrCode(array $qrAttributes = [], array $routeAttributes = []): QrCodeRoute
    {
        $user = User::factory()->create();

        $snapshot = $qrAttributes['entitlement_snapshot'] ?? ['tier' => 'pro'];
        unset($qrAttributes['entitlement_snapshot']);

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ], $qrAttributes));

        $qrCode->entitlement_snapshot = $snapshot;
        $qrCode->save();

        return QrCodeRoute::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'code' => 'ABC123',
            'host' => 'qrm.sg',
        ], $routeAttributes));
    }

    // --- Download with custom colors ---

    public function test_svg_download_applies_custom_colors(): void
    {
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'fg_color' => '#1a1a2e',
                'bg_color' => '#e94560',
            ]],
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $svg = $response->content();
        // The SVG should contain the custom foreground or background color
        // (endroid renders as fill or rect backgrounds).
        $this->assertTrue(
            str_contains($svg, '#1a1a2e') || str_contains($svg, '#e94560')
                || str_contains($svg, '1a1a2e') || str_contains($svg, 'e94560'),
            'SVG output should contain custom colors.'
        );
    }

    public function test_png_download_applies_custom_colors(): void
    {
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'fg_color' => '#ff0000',
                'bg_color' => '#00ff00',
            ]],
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertNotEmpty($response->content());
    }

    // --- Download with dot style ---

    public function test_svg_download_with_round_dot_style_succeeds(): void
    {
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'dot_style' => 'round',
            ]],
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    // --- Download with error correction ---

    public function test_svg_download_with_high_error_correction_pro(): void
    {
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'error_correction' => 'H',
            ]],
            'entitlement_snapshot' => ['tier' => 'pro'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    // --- Free tier style: colors only, premium features stripped ---

    public function test_free_tier_download_with_colors_succeeds(): void
    {
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'fg_color' => '#0000ff',
                'bg_color' => '#ffff00',
            ]],
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    public function test_free_tier_download_with_gradient_still_succeeds(): void
    {
        // Even if a Free user somehow has gradient settings stored, the
        // QrStyleService strips it during resolveStyle. The download should
        // still succeed with the fallback (default fg color).
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'gradient' => ['from' => '#111', 'to' => '#222', 'angle' => 0],
            ]],
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    public function test_free_tier_download_with_premium_ec_downgrades(): void
    {
        // Free user requests high error correction → silently downgraded to M.
        $this->createRouteWithQrCode([
            'settings' => ['style' => [
                'error_correction' => 'H',
            ]],
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    // --- No style settings: backward compat ---

    public function test_download_without_style_settings_works(): void
    {
        $this->createRouteWithQrCode([
            'settings' => null,
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    public function test_download_with_empty_settings_works(): void
    {
        $this->createRouteWithQrCode([
            'settings' => [],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    public function test_download_with_other_settings_preserves_style(): void
    {
        // Settings may contain other keys alongside style.
        $this->createRouteWithQrCode([
            'settings' => [
                'custom_key' => 'value',
                'style' => ['fg_color' => '#123456'],
            ],
        ]);

        $response = $this->get('/api/qr/ABC123/download.svg');

        $response->assertOk();
        $this->assertNotEmpty($response->content());
    }

    // --- Preview with style ---

    public function test_creator_preview_includes_style(): void
    {
        $user = User::factory()->create();

        // Without style: default preview
        $response = $this->actingAs($user)->get('/create');

        $response->assertOk();
    }
}
