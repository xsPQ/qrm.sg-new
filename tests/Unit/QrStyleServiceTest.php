<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Services\QrStyleService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Tests\TestCase;

class QrStyleServiceTest extends TestCase
{
    private QrStyleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrStyleService();
    }

    // --- resolveStyle: defaults ---

    public function test_resolve_style_returns_defaults_for_empty_input_free(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle(null, $snapshot);

        $this->assertSame('#000000', $style['fg_color']);
        $this->assertSame('#ffffff', $style['bg_color']);
        $this->assertSame('square', $style['dot_style']);
        $this->assertSame('M', $style['error_correction']);
        $this->assertSame(10, $style['margin']);
    }

    // --- resolveStyle: colors available on all tiers ---

    public function test_free_can_set_custom_colors(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'fg_color' => '#1a1a2e',
            'bg_color' => '#e94560',
        ], $snapshot);

        $this->assertSame('#1a1a2e', $style['fg_color']);
        $this->assertSame('#e94560', $style['bg_color']);
    }

    public function test_pro_can_set_custom_colors(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'fg_color' => '#ff0000',
            'bg_color' => '#00ff00',
        ], $snapshot);

        $this->assertSame('#ff0000', $style['fg_color']);
        $this->assertSame('#00ff00', $style['bg_color']);
    }

    // --- resolveStyle: dot style available on all tiers ---

    public function test_free_can_set_dot_style(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'dot_style' => 'round',
        ], $snapshot);

        $this->assertSame('round', $style['dot_style']);
    }

    public function test_invalid_dot_style_falls_back_to_square(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'dot_style' => 'invalid_style',
        ], $snapshot);

        $this->assertSame('square', $style['dot_style']);
    }

    // --- resolveStyle: error correction gating ---

    public function test_free_cannot_use_premium_error_correction(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'error_correction' => 'H',
        ], $snapshot);

        // Free tier is downgraded to Medium
        $this->assertSame('M', $style['error_correction']);
    }

    public function test_pro_can_use_premium_error_correction(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'error_correction' => 'H',
        ], $snapshot);

        $this->assertSame('H', $style['error_correction']);
    }

    // --- resolveStyle: gradient gating ---

    public function test_free_cannot_use_gradient(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'gradient' => ['from' => '#1a1a2e', 'to' => '#e94560', 'angle' => 45],
        ], $snapshot);

        // Gradient is stripped for Free tier
        $this->assertArrayNotHasKey('gradient', $style);
    }

    public function test_pro_can_use_gradient(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'gradient' => ['from' => '#1a1a2e', 'to' => '#e94560', 'angle' => 45],
        ], $snapshot);

        $this->assertArrayHasKey('gradient', $style);
        $this->assertSame('#1a1a2e', $style['gradient']['from']);
        $this->assertSame('#e94560', $style['gradient']['to']);
        $this->assertSame(45, $style['gradient']['angle']);
    }

    // --- resolveStyle: logo gating ---

    public function test_free_cannot_use_logo(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'logo_path' => '/storage/logos/test.png',
            'logo_size' => 100,
        ], $snapshot);

        $this->assertArrayNotHasKey('logo_path', $style);
        $this->assertArrayNotHasKey('logo_size', $style);
    }

    public function test_pro_can_use_logo(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'logo_path' => '/storage/logos/test.png',
            'logo_size' => 100,
        ], $snapshot);

        $this->assertArrayHasKey('logo_path', $style);
        $this->assertSame(100, $style['logo_size']);
    }

    // --- resolveStyle: margin clamping ---

    public function test_margin_is_clamped_to_valid_range(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $styleHigh = $this->service->resolveStyle(['margin' => 999], $snapshot);
        $this->assertSame(50, $styleHigh['margin']);

        $styleLow = $this->service->resolveStyle(['margin' => -5], $snapshot);
        $this->assertSame(0, $styleLow['margin']);
    }

    // --- resolveStyle: hex sanitization ---

    public function test_invalid_hex_falls_back_to_default(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'fg_color' => 'not-a-color',
            'bg_color' => '#abc',
        ], $snapshot);

        $this->assertSame('#000000', $style['fg_color']);
        $this->assertSame('#ffffff', $style['bg_color']);
    }

    public function test_hex_is_normalized_to_lowercase(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $style = $this->service->resolveStyle([
            'fg_color' => '#AA00FF',
        ], $snapshot);

        $this->assertSame('#aa00ff', $style['fg_color']);
    }

    // --- usesPremiumFeatures ---

    public function test_uses_premium_features_detects_gradient(): void
    {
        $this->assertTrue($this->service->usesPremiumFeatures([
            'gradient' => ['from' => '#111', 'to' => '#222'],
        ]));
    }

    public function test_uses_premium_features_detects_logo(): void
    {
        $this->assertTrue($this->service->usesPremiumFeatures([
            'logo_path' => '/some/path.png',
        ]));
    }

    public function test_uses_premium_features_detects_premium_ec(): void
    {
        $this->assertTrue($this->service->usesPremiumFeatures([
            'error_correction' => 'Q',
        ]));
        $this->assertTrue($this->service->usesPremiumFeatures([
            'error_correction' => 'H',
        ]));
    }

    public function test_uses_premium_features_returns_false_for_basic_style(): void
    {
        $this->assertFalse($this->service->usesPremiumFeatures([
            'fg_color' => '#ff0000',
            'bg_color' => '#ffffff',
            'dot_style' => 'round',
            'error_correction' => 'M',
        ]));
    }

    public function test_uses_premium_features_returns_false_for_null(): void
    {
        $this->assertFalse($this->service->usesPremiumFeatures(null));
    }

    // --- featureFlags ---

    public function test_feature_flags_free(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_FREE);

        $flags = $this->service->featureFlags($snapshot);

        $this->assertTrue($flags['can_set_colors']);
        $this->assertTrue($flags['can_set_dot_style']);
        $this->assertFalse($flags['can_use_gradient']);
        $this->assertFalse($flags['can_use_logo']);
        $this->assertFalse($flags['can_use_premium_ec']);
    }

    public function test_feature_flags_pro(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $flags = $this->service->featureFlags($snapshot);

        $this->assertTrue($flags['can_set_colors']);
        $this->assertTrue($flags['can_set_dot_style']);
        $this->assertTrue($flags['can_use_gradient']);
        $this->assertTrue($flags['can_use_logo']);
        $this->assertTrue($flags['can_use_premium_ec']);
    }

    // --- applyStyle: integration ---

    public function test_apply_style_produces_valid_png_with_custom_colors(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'fg_color' => '#1a1a2e',
            'bg_color' => '#e94560',
            'dot_style' => 'round',
        ], $snapshot);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data('https://example.com')
            ->size(200)
            ->margin(10);

        $this->service->applyStyle($builder, $style);

        $result = $builder->build();

        $this->assertNotEmpty($result->getString());
        $this->assertStringStartsWith('data:image/png;base64,', $result->getDataUri());
    }

    public function test_apply_style_with_gradient_produces_valid_png(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'fg_color' => '#000000',
            'bg_color' => '#ffffff',
            'gradient' => ['from' => '#1a1a2e', 'to' => '#e94560', 'angle' => 45],
        ], $snapshot);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data('https://example.com')
            ->size(200)
            ->margin(10);

        $this->service->applyStyle($builder, $style);

        $result = $builder->build();

        $this->assertNotEmpty($result->getString());
    }

    public function test_apply_style_with_nonexistent_logo_does_not_crash(): void
    {
        $snapshot = EntitlementSnapshot::forPlan(EntitlementSnapshot::PLAN_PRO);

        $style = $this->service->resolveStyle([
            'logo_path' => '/nonexistent/logo.png',
        ], $snapshot);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data('https://example.com')
            ->size(200)
            ->margin(10);

        $this->service->applyStyle($builder, $style);

        // Should build without error even though the logo file doesn't exist.
        $result = $builder->build();

        $this->assertNotEmpty($result->getString());
    }
}
