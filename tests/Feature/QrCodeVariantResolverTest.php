<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Exceptions\FeatureNotEntitledException;
use App\Models\QrCode;
use App\Models\QrCodeVariant;
use App\Models\Scan;
use App\Models\User;
use App\Services\QrCodeResolver;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

/**
 * FEAT-06: Feature tests for the A/B testing QR code resolver flow.
 *
 * Tests the full resolve path: QR code with variants → resolver selects a
 * variant → redirects to variant URL → variant scan_count incremented →
 * scan record stamped with variant_id.
 */
class QrCodeVariantResolverTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new QrCodeResolver();
    }

    /**
     * Create a Pro-entitled QR code with the given variants.
     */
    private function createQrCodeWithVariants(array $variantsData, array $qrAttributes = []): QrCode
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create(array_merge([
            'user_id' => $user->id,
            'title' => 'A/B Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://default.example.com'],
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
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

        return $qrCode->fresh(['variants']);
    }

    public function test_resolver_redirects_to_variant_url_when_variants_exist(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://variant-a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('variant-a.example.com', $response->getTargetUrl());
    }

    public function test_resolver_increments_variant_scan_count(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $this->resolver->resolve($qrCode, $request);

        $variant = QrCodeVariant::where('qr_code_id', $qrCode->id)->first();
        $this->assertEquals(1, $variant->scan_count);
    }

    public function test_resolver_stamps_variant_id_on_scan_record(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $this->resolver->resolve($qrCode, $request);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();
        $this->assertNotNull($scan);
        $this->assertNotNull($scan->qr_code_variant_id);

        $variant = QrCodeVariant::find($scan->qr_code_variant_id);
        $this->assertSame('A', $variant->label);
    }

    public function test_resolver_without_variants_does_not_stamp_variant_id(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Normal QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
        ]);

        $request = Request::create('/r/test');
        $this->resolver->resolve($qrCode, $request);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();
        $this->assertNotNull($scan);
        $this->assertNull($scan->qr_code_variant_id);
    }

    public function test_resolver_device_strategy_picks_correct_variant(): void
    {
        $qrCode = $this->createQrCodeWithVariants(
            [
                ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
                ['label' => 'B', 'url' => 'https://desktop.example.com', 'device_target' => 'desktop'],
            ],
            [
                'settings' => ['ab_testing' => ['strategy' => 'device']],
            ],
        );

        // Mobile request → should pick variant A
        $mobileRequest = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);
        $response = $this->resolver->resolve($qrCode, $mobileRequest);

        $this->assertStringContainsString('mobile.example.com', $response->getTargetUrl());

        $variant = QrCodeVariant::where('label', 'A')->first();
        $this->assertEquals(1, $variant->scan_count);

        // Desktop request → should pick variant B
        $qrCode->refresh();
        $qrCode->load('variants');
        $desktopRequest = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
        $response2 = $this->resolver->resolve($qrCode, $desktopRequest);

        $this->assertStringContainsString('desktop.example.com', $response2->getTargetUrl());

        $variantB = QrCodeVariant::where('label', 'B')->first();
        $this->assertEquals(1, $variantB->scan_count);
    }

    public function test_resolver_random_strategy_distributes_to_valid_variants(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com', 'weight' => 1],
            ['label' => 'B', 'url' => 'https://b.example.com', 'weight' => 1],
            ['label' => 'C', 'url' => 'https://c.example.com', 'weight' => 1],
        ]);

        $request = Request::create('/r/test');

        // Resolve multiple times and verify we only ever redirect to variant URLs.
        $validUrls = ['a.example.com', 'b.example.com', 'c.example.com'];

        for ($i = 0; $i < 10; $i++) {
            $qrCode->refresh();
            $qrCode->load('variants');
            $qrCode->scan_count = 0;
            $qrCode->status = 'active';

            // Re-consume the scan count manually since we can't call resolve
            // multiple times on a non-burn code (atomic consume increments).
            // Instead, test the selection logic directly.
            $selector = new \App\Services\VariantSelector();
            $variant = $selector->selectByStrategy(
                $qrCode->variants,
                'random',
                $request,
            );

            $this->assertContains($variant->url, [
                'https://a.example.com',
                'https://b.example.com',
                'https://c.example.com',
            ]);
        }
    }

    public function test_resolver_increments_qr_code_scan_count_with_variants(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);

        $request = Request::create('/r/test');
        $this->resolver->resolve($qrCode, $request);

        $qrCode->refresh();
        // The QR code's own scan_count should still increment.
        $this->assertEquals(1, $qrCode->scan_count);
    }

    public function test_resolver_stamps_scan_record_with_variant_id_for_device_strategy(): void
    {
        $qrCode = $this->createQrCodeWithVariants(
            [
                ['label' => 'A', 'url' => 'https://mobile.example.com', 'device_target' => 'mobile'],
                ['label' => 'B', 'url' => 'https://desktop.example.com', 'device_target' => 'desktop'],
            ],
            [
                'settings' => ['ab_testing' => ['strategy' => 'device']],
            ],
        );

        $request = Request::create('/r/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ]);

        $this->resolver->resolve($qrCode, $request);

        $scan = Scan::where('qr_code_id', $qrCode->id)->first();
        $variant = QrCodeVariant::find($scan->qr_code_variant_id);

        $this->assertSame('A', $variant->label);
        $this->assertSame('mobile', $variant->device_target);
    }

    public function test_resolver_works_with_redirect_type_qr_codes(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Redirect with variants',
            'type' => 'redirect',
            'content' => ['default_url' => 'https://default.example.com', 'rules' => []],
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
        ]);

        QrCodeVariant::create([
            'qr_code_id' => $qrCode->id,
            'label' => 'A',
            'url' => 'https://variant-a.example.com',
            'weight' => 1,
            'sort_order' => 0,
            'scan_count' => 0,
        ]);

        $qrCode = $qrCode->fresh('variants');

        $request = Request::create('/r/test');
        $response = $this->resolver->resolve($qrCode, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('variant-a.example.com', $response->getTargetUrl());
    }

    // --- Entitlement gating tests ---

    public function test_free_tier_qr_code_cannot_sync_variants(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Free QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('free')->toArray(),
        ]);

        $service = app(QrCodeService::class);

        $this->expectException(FeatureNotEntitledException::class);

        $service->syncVariants($qrCode, [
            ['label' => 'A', 'url' => 'https://a.example.com'],
            ['label' => 'B', 'url' => 'https://b.example.com'],
        ]);
    }

    public function test_pro_tier_qr_code_can_sync_variants(): void
    {
        $user = User::factory()->create();
        $qrCode = new QrCode();
        $qrCode->user_id = $user->id;
        $qrCode->title = 'Pro QR';
        $qrCode->type = 'url';
        $qrCode->content = ['url' => 'https://example.com'];
        $qrCode->status = 'active';
        $qrCode->entitlement_snapshot = EntitlementSnapshot::forPlan('pro')->toArray();
        $qrCode->saveQuietly();

        $service = app(QrCodeService::class);

        $service->syncVariants($qrCode, [
            ['label' => 'A', 'url' => 'https://a.example.com'],
            ['label' => 'B', 'url' => 'https://b.example.com'],
        ]);

        $this->assertDatabaseCount('qr_code_variants', 2);
        $this->assertDatabaseHas('qr_code_variants', [
            'qr_code_id' => $qrCode->id,
            'label' => 'A',
            'url' => 'https://a.example.com',
        ]);
        $this->assertDatabaseHas('qr_code_variants', [
            'qr_code_id' => $qrCode->id,
            'label' => 'B',
            'url' => 'https://b.example.com',
        ]);
    }

    public function test_clear_variants_removes_all_variants(): void
    {
        $qrCode = $this->createQrCodeWithVariants([
            ['label' => 'A', 'url' => 'https://a.example.com'],
            ['label' => 'B', 'url' => 'https://b.example.com'],
        ]);

        $service = app(QrCodeService::class);
        $service->clearVariants($qrCode);

        $this->assertDatabaseCount('qr_code_variants', 0);
    }

    public function test_entitlement_gate_includes_ab_testing_flag(): void
    {
        $gate = app(\App\Domain\Entitlement\EntitlementGate::class);

        $freeFlags = $gate->featureFlags(EntitlementSnapshot::forPlan('free'));
        $proFlags = $gate->featureFlags(EntitlementSnapshot::forPlan('pro'));

        $this->assertFalse($freeFlags['can_use_ab_testing']);
        $this->assertTrue($proFlags['can_use_ab_testing']);
    }
}
