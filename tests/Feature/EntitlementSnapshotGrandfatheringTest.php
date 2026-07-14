<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Domain\Entitlement\EntitlementGate;
use App\Models\QrCode;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bestandsschutz / grandfathering on plan downgrade (Pflichtenheft §3.5.3):
 * an existing QR code keeps its creation-time entitlement snapshot forever,
 * a downgrade never overwrites it, grandfathered codes do not count against
 * the Free limit, and only newly created codes take the current plan's rights.
 */
class EntitlementSnapshotGrandfatheringTest extends TestCase
{
    use RefreshDatabase;

    private QrCodeService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QrCodeService(new QrCodeRouteService, new EntitlementGate);
        $this->user = User::factory()->create();
    }

    public function test_paid_code_captures_full_snapshot_at_creation(): void
    {
        $this->grantPro();

        $code = $this->service->create($this->user, [
            'title' => 'Pro code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $snapshot = $code->entitlementSnapshot();

        $this->assertSame('pro', $snapshot->plan());
        $this->assertTrue($snapshot->allowsCustomAlias());
        $this->assertSame('high_resolution', $snapshot->downloadProfile());
        // Stored canonical shape includes the legacy `tier` alias.
        $this->assertSame('pro', $code->entitlement_snapshot['tier']);
        // Paid codes have no server-side expiry.
        $this->assertNull($code->expires_at);
    }

    public function test_downgrade_leaves_existing_snapshot_untouched(): void
    {
        $this->grantPro();
        $code = $this->service->create($this->user, [
            'title' => 'Pro code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        // Tarif-Downgrade: the active subscription ends.
        $this->revokePro();

        $reloaded = QrCode::find($code->id);

        // Bestandsschutz: snapshot, plan-derived rights and lack of expiry persist.
        $this->assertSame('pro', $reloaded->entitlementSnapshot()->plan());
        $this->assertTrue($reloaded->entitlementSnapshot()->allowsCustomAlias());
        $this->assertNull($reloaded->expires_at);
    }

    public function test_grandfathered_codes_do_not_count_against_free_limit_after_downgrade(): void
    {
        config(['qr.free.max_active_qr_codes' => 2]);

        $this->grantPro();
        // Several codes created while on a paid plan.
        for ($i = 0; $i < 5; $i++) {
            $this->service->create($this->user, [
                'title' => "Pro QR {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        $this->revokePro(); // Downgrade to Free.

        // Grandfathered codes are excluded from the Free count.
        $this->assertSame(0, $this->service->countActiveForFreeTier($this->user));

        // A new Free code can still be created (headroom is full).
        $this->service->create($this->user, [
            'title' => 'New free code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertSame(1, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_new_codes_after_downgrade_get_free_rights(): void
    {
        $this->grantPro();
        $this->revokePro();

        $code = $this->service->create($this->user, [
            'title' => 'New free code',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $snapshot = $code->entitlementSnapshot();

        $this->assertTrue($snapshot->isFree());
        $this->assertFalse($snapshot->allowsCustomAlias());
        $this->assertSame('qrm.sg', $snapshot->branding());
        // Free codes auto-expire.
        $this->assertNotNull($code->expires_at);
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

    private function revokePro(): void
    {
        Subscription::where('user_id', $this->user->id)->delete();
    }
}
