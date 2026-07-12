<?php

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use App\Models\User;
use App\Services\FairUseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-08: Fair-Use Policy enforcement
 */
class FairUsePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_create_within_limit(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $fairUse = app(FairUseService::class);
        $result = $fairUse->canCreateQrCode($user);

        $this->assertTrue($result['allowed']);
    }

    public function test_free_user_blocked_after_max_active(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        // Create 10 active QR codes (the free limit)
        for ($i = 0; $i < 10; $i++) {
            QrCode::create([
                'user_id' => $user->id,
                'title' => "Code {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
                'status' => 'active',
                'entitlement_snapshot' => EntitlementSnapshot::forPlan('free')->toArray(),
            ]);
        }

        $fairUse = app(FairUseService::class);
        $result = $fairUse->canCreateQrCode($user);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('max_active_reached', $result['reason']);
    }

    public function test_pro_user_has_higher_limit(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);

        $fairUse = app(FairUseService::class);
        $limits = $fairUse->limitsForPlan('pro');

        $this->assertEquals(500, $limits['max_active_qr_codes']);
    }

    public function test_business_user_has_highest_limit(): void
    {
        $fairUse = app(FairUseService::class);
        $limits = $fairUse->limitsForPlan('business');

        $this->assertEquals(5000, $limits['max_active_qr_codes']);
        $this->assertEquals(500000, $limits['max_scans_per_day']);
    }

    public function test_rate_limit_on_creation(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $fairUse = app(FairUseService::class);

        // Exhaust the per-hour creation limit (5 for free)
        for ($i = 0; $i < 5; $i++) {
            $fairUse->recordQrCreation($user);
        }

        $result = $fairUse->canCreateQrCode($user);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('rate_limited', $result['reason']);
    }

    public function test_agb_page_loads(): void
    {
        $response = $this->get('/agb');

        $response->assertOk();
        $response->assertSee('Allgemeine Geschäftsbedingungen');
        $response->assertSee('Fair-Use-Policy');
    }

    public function test_terms_page_loads(): void
    {
        $response = $this->get('/terms');

        $response->assertOk();
        $response->assertSee('Terms of Service');
        $response->assertSee('Fair-Use Policy');
    }

    public function test_agb_contains_all_required_sections(): void
    {
        $response = $this->get('/agb');

        $response->assertSee('Leistungsbeschreibung');
        $response->assertSee('Zahlungsbedingungen');
        $response->assertSee('Kündigung');
        $response->assertSee('Haftungsausschluss');
        $response->assertSee('Datenschutz');
        $response->assertSee('Gerichtsstand');
    }

    public function test_max_ab_variants_per_plan(): void
    {
        $fairUse = app(FairUseService::class);

        $this->assertEquals(5, $fairUse->maxAbVariants('pro'));
        $this->assertEquals(20, $fairUse->maxAbVariants('business'));
    }

    public function test_scan_limit_check_for_pro_code(): void
    {
        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'entitlement_snapshot' => EntitlementSnapshot::forPlan('pro')->toArray(),
        ]);

        $fairUse = app(FairUseService::class);

        // With 0 scans, should be allowed
        $this->assertTrue($fairUse->checkScanLimit($qrCode));
    }
}
