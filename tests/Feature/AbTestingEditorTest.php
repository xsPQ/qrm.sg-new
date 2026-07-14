<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeVariant;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\VariantSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A/B Testing tests (FEAT-06).
 * Backend logic tests — editor Blade UI pending.
 */
class AbTestingEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_selector_weighted_random_picks_valid_variant(): void
    {
        $selector = app(VariantSelector::class);
        $variants = QrCodeVariant::factory()->count(2)->create([
            'weight' => 1,
            'device_target' => null,
        ]);

        $picked = $selector->selectWeightedRandom($variants);
        $this->assertContains($picked->id, $variants->pluck('id')->toArray());
    }

    public function test_variant_selector_device_based_picks_mobile(): void
    {
        $selector = app(VariantSelector::class);
        $desktop = QrCodeVariant::factory()->create(['device_target' => 'desktop', 'weight' => 1]);
        $mobile = QrCodeVariant::factory()->create(['device_target' => 'mobile', 'weight' => 1]);

        $variants = QrCodeVariant::where('qr_code_id', $desktop->qr_code_id)->orWhere('qr_code_id', $mobile->qr_code_id)->get();
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');
        $picked = $selector->selectByDevice($variants, $request);
        $this->assertSame($mobile->id, $picked->id);
    }

    public function test_pro_user_can_sync_variants_via_service(): void
    {
        $user = User::factory()->create();
        $qrCode = new QrCode();
        $qrCode->user_id = $user->id;
        $qrCode->title = 'Pro QR';
        $qrCode->type = 'url';
        $qrCode->content = ['url' => 'https://example.com'];
        $qrCode->status = 'active';
        $qrCode->entitlement_snapshot = \App\Domain\Entitlement\EntitlementSnapshot::forPlan('pro')->toArray();
        $qrCode->saveQuietly();

        $service = app(QrCodeService::class);
        $service->syncVariants($qrCode, [
            ['label' => 'A', 'url' => 'https://a.example.com'],
            ['label' => 'B', 'url' => 'https://b.example.com'],
        ]);

        $this->assertDatabaseCount('qr_code_variants', 2);
    }

    public function test_free_user_cannot_sync_variants(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Free QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        $service = app(QrCodeService::class);

        $this->expectException(\App\Exceptions\FeatureNotEntitledException::class);
        $service->syncVariants($qrCode, [
            ['label' => 'A', 'url' => 'https://a.example.com'],
        ]);
    }
}
