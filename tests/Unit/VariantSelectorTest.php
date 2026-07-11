<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\QrCodeVariant;
use App\Models\User;
use App\Services\VariantSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * FEAT-06: Unit tests for the VariantSelector A/B testing logic.
 *
 * Covers weighted-random distribution, device-based selection, and edge cases
 * (single variant, zero weights, no device match).
 */
class VariantSelectorTest extends TestCase
{
    use RefreshDatabase;

    private VariantSelector $selector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selector = new VariantSelector();
    }

    private function createQrCodeWithVariants(array $variantsData, array $qrAttributes = []): QrCode
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'A/B Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://default.example.com'],
        ], $qrAttributes));

        $sortOrder = 0;
        foreach ($variantsData as $data) {
            QrCodeVariant::create([
                'qr_code_id' => $qrCode->id,
                'label' => $data['label'],
                'url' => $data['url'],
                'weight' => $data['weight'] ?? 1,
                'device_target' => $data['device_target'] ?? null,
                'sort_order' => $sortOrder++,
                'scan_count' => 0,
            ]);
        }

        return $qrCode->fresh('variants');
    }

    // --- Weighted Random Selection ---

    public function test_weighted_random_returns_single_variant(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_RANDOM,
            $request,
        );

        $this->assertSame('A', $variant->label);
    }

    public function test_weighted_random_distributes_according_to_weights(): void
    {
        // Variant A: weight 3, Variant B: weight 1 → expect ~75% / ~25%
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com', 'weight' => 3],
            ['label' => 'B', 'url' => 'https://b.example.com', 'weight' => 1],
        ]);

        $request = Request::create('/r/test');
        $counts = ['A' => 0, 'B' => 0];

        for ($i = 0; $i < 1000; $i++) {
            $variant = $this->selector->selectByStrategy(
                $qrCode->variants,
                VariantSelector::STRATEGY_RANDOM,
                $request,
            );
            $counts[$variant->label]++;
        }

        // With 1000 iterations, A should get 60-90% (allowing variance).
        $this->assertGreaterThan(600, $counts['A'], "Variant A should be selected ~75% of the time, got {$counts['A']}/1000");
        $this->assertLessThan(900, $counts['A'], "Variant A should not exceed ~90%, got {$counts['A']}/1000");
    }

    public function test_weighted_random_with_equal_weights_distributes_evenly(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com', 'weight' => 1],
            ['label' => 'B', 'url' => 'https://b.example.com', 'weight' => 1],
        ]);

        $request = Request::create('/r/test');
        $counts = ['A' => 0, 'B' => 0];

        for ($i = 0; $i < 1000; $i++) {
            $variant = $this->selector->selectByStrategy(
                $qrCode->variants,
                VariantSelector::STRATEGY_RANDOM,
                $request,
            );
            $counts[$variant->label]++;
        }

        // With equal weights, both should be ~50% ± 10%.
        $this->assertGreaterThan(400, $counts['A']);
        $this->assertLessThan(600, $counts['A']);
    }

    public function test_weighted_random_falls_back_when_all_weights_zero(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com', 'weight' => 0],
            ['label' => 'B', 'url' => 'https://b.example.com', 'weight' => 0],
        ]);

        $request = Request::create('/r/test');
        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_RANDOM,
            $request,
        );

        // Should return the first variant as fallback.
        $this->assertSame('A', $variant->label);
    }

    // --- Device-Based Selection ---

    public function test_device_strategy_selects_mobile_variant(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
            ['label' => 'B', 'url' => 'https://desktop.example.com', 'device_target' => 'desktop'],
        ]);

        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);

        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_DEVICE,
            $request,
        );

        $this->assertSame('A', $variant->label);
        $this->assertStringContainsString('mobile.example.com', $variant->url);
    }

    public function test_device_strategy_selects_desktop_variant(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
            ['label' => 'B', 'url' => 'https://desktop.example.com', 'device_target' => 'desktop'],
        ]);

        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_DEVICE,
            $request,
        );

        $this->assertSame('B', $variant->label);
        $this->assertStringContainsString('desktop.example.com', $variant->url);
    }

    public function test_device_strategy_falls_back_to_first_when_no_match(): void
    {
        // All variants target tablet, but the scanner is desktop.
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://tablet1.example.com', 'device_target' => 'tablet'],
            ['label' => 'B', 'url' => 'https://tablet2.example.com', 'device_target' => 'tablet'],
        ]);

        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_DEVICE,
            $request,
        );

        // Should fall back to the first variant.
        $this->assertSame('A', $variant->label);
    }

    public function test_device_strategy_falls_back_to_null_target_variant(): void
    {
        // Variant C has no device target — should be the catch-all fallback.
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
            ['label' => 'C', 'url' => 'https://fallback.example.com', 'device_target' => null],
        ]);

        // Desktop scanner, no desktop-targeted variant.
        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $variant = $this->selector->selectByStrategy(
            $qrCode->variants,
            VariantSelector::STRATEGY_DEVICE,
            $request,
        );

        $this->assertSame('C', $variant->label);
    }

    // --- selectAndIncrement (integration with DB) ---

    public function test_select_and_increment_returns_null_for_no_variants(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'No Variants',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $request = Request::create('/r/test');
        $result = $this->selector->selectAndIncrement($qrCode, $request);

        $this->assertNull($result);
    }

    public function test_select_and_increment_increments_scan_count(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $variant = $this->selector->selectAndIncrement($qrCode, $request);

        $this->assertNotNull($variant);
        $this->assertSame(1, $variant->scan_count);

        // Verify it was persisted to DB.
        $this->assertEquals(1, QrCodeVariant::find($variant->id)->scan_count);
    }

    public function test_select_and_increment_increments_correct_variant(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
            ['label' => 'B', 'url' => 'https://desktop.example.com', 'device_target' => 'desktop'],
        ]);

        $qrCode->settings = ['ab_testing' => ['strategy' => 'device']];
        $qrCode->save();
        $qrCode->load('variants');

        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);

        $variant = $this->selector->selectAndIncrement($qrCode, $request);

        $this->assertSame('A', $variant->label);
        $this->assertSame(1, QrCodeVariant::where('label', 'A')->first()->scan_count);
        $this->assertSame(0, QrCodeVariant::where('label', 'B')->first()->scan_count);
    }
}
