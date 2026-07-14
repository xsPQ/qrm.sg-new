<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\QrCodeExpiringSoon;
use App\Exceptions\FreeTierLimitExceededException;
use App\Models\QrCode;
use App\Models\Subscription;
use App\Models\User;
use App\Domain\Entitlement\EntitlementGate;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Free-Tier active-QR limit enforcement (Pflichtenheft §2.4: "Nach 10 QR-Codes
 * → Upgrade-Prompt"). Covers counting rules (active, non-expired, non-burned,
 * non-maxed), the 11th-creation rejection, paid-tier bypass, Bestandsschutz
 * for grandfathered codes, the status payload, and the expiring-soon hook.
 */
class QrCodeFreeTierLimitTest extends TestCase
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

    public function test_count_active_for_free_tier_counts_only_active_codes(): void
    {
        $this->makeActiveFreeCode($this->user);
        $this->makeActiveFreeCode($this->user);

        $this->assertSame(2, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_count_excludes_expired_codes(): void
    {
        $this->makeActiveFreeCode($this->user);
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Expired', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->subDay(),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertSame(1, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_count_excludes_burned_codes(): void
    {
        $this->makeActiveFreeCode($this->user);
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Burned', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'burn' => true, 'scan_count' => 1,
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertSame(1, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_count_excludes_maxed_out_codes(): void
    {
        $this->makeActiveFreeCode($this->user);
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Maxed', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'max_scans' => 5, 'scan_count' => 5,
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertSame(1, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_count_excludes_grandfathered_paid_codes(): void
    {
        // A code created under a paid tier (Bestandsschutz, §3.5.3) must not
        // count against the Free limit even while the user is now Free.
        $this->makeActiveFreeCode($this->user);
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Grandfathered Pro', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'entitlement_snapshot' => ['tier' => 'pro', 'source' => 'stripe'],
        ]);

        $this->assertSame(1, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_free_user_can_create_up_to_limit(): void
    {
        config(['qr.free.max_active_qr_codes' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->service->create($this->user, [
                'title' => "QR {$i}", 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        $this->assertSame(3, $this->service->countActiveForFreeTier($this->user));
    }

    public function test_eleventh_creation_is_rejected_for_free_user(): void
    {
        config(['qr.free.max_active_qr_codes' => 10]);

        for ($i = 0; $i < 10; $i++) {
            $this->service->create($this->user, [
                'title' => "QR {$i}", 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        $this->expectException(FreeTierLimitExceededException::class);
        $this->service->create($this->user, [
            'title' => 'Eleventh', 'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);
    }

    public function test_rejection_exception_carries_active_count_and_limit(): void
    {
        config(['qr.free.max_active_qr_codes' => 2]);

        for ($i = 0; $i < 2; $i++) {
            $this->service->create($this->user, [
                'title' => "QR {$i}", 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        try {
            $this->service->create($this->user, [
                'title' => 'Over', 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
            $this->fail('Expected FreeTierLimitExceededException.');
        } catch (FreeTierLimitExceededException $e) {
            $this->assertSame(2, $e->activeCount);
            $this->assertSame(2, $e->limit);
        }
    }

    public function test_business_tier_is_not_subject_to_limit(): void
    {
        Subscription::create([
            'user_id' => $this->user->id, 'stripe_id' => 'cus_b_1',
            'stripe_status' => 'active', 'stripe_price' => 'price_business', 'quantity' => 1,
        ]);

        config(['qr.free.max_active_qr_codes' => 2]);

        // A paid user must sail past the Free limit.
        for ($i = 0; $i < 5; $i++) {
            $this->service->create($this->user, [
                'title' => "Business QR {$i}", 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        $this->assertSame(5, QrCode::where('user_id', $this->user->id)->count());
    }

    public function test_pro_tier_is_not_subject_to_limit(): void
    {
        Subscription::create([
            'user_id' => $this->user->id, 'stripe_id' => 'cus_p_1',
            'stripe_status' => 'active', 'stripe_price' => 'price_pro', 'quantity' => 1,
        ]);

        config(['qr.free.max_active_qr_codes' => 2]);

        for ($i = 0; $i < 4; $i++) {
            $this->service->create($this->user, [
                'title' => "Pro QR {$i}", 'type' => 'url',
                'content' => ['url' => 'https://example.com'],
            ]);
        }

        $this->assertSame(4, QrCode::where('user_id', $this->user->id)->count());
    }

    public function test_free_tier_status_reports_remaining_and_no_upgrade_below_limit(): void
    {
        config(['qr.free.max_active_qr_codes' => 10]);

        $this->makeActiveFreeCode($this->user);
        $this->makeActiveFreeCode($this->user);

        $status = $this->service->freeTierStatus($this->user);

        $this->assertSame('free', $status['tier']);
        $this->assertSame(2, $status['active_count']);
        $this->assertSame(10, $status['limit']);
        $this->assertSame(8, $status['remaining']);
        $this->assertFalse($status['limit_reached']);
        $this->assertFalse($status['upgrade_required']);
        $this->assertNull($status['upgrade_hint']);
    }

    public function test_free_tier_status_flags_upgrade_when_limit_reached(): void
    {
        config(['qr.free.max_active_qr_codes' => 2]);

        $this->makeActiveFreeCode($this->user);
        $this->makeActiveFreeCode($this->user);

        $status = $this->service->freeTierStatus($this->user);

        $this->assertTrue($status['limit_reached']);
        $this->assertTrue($status['upgrade_required']);
        $this->assertSame(0, $status['remaining']);
        $this->assertNotNull($status['upgrade_hint']);
    }

    public function test_free_tier_status_for_paid_user_has_no_limit(): void
    {
        Subscription::create([
            'user_id' => $this->user->id, 'stripe_id' => 'cus_b_2',
            'stripe_status' => 'active', 'stripe_price' => 'price_business', 'quantity' => 1,
        ]);

        $status = $this->service->freeTierStatus($this->user);

        $this->assertSame('business', $status['tier']);
        $this->assertNull($status['limit']);
        $this->assertNull($status['remaining']);
        $this->assertFalse($status['upgrade_required']);
    }

    public function test_is_expiring_soon_true_within_warning_window(): void
    {
        $qrCode = $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Soon', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(2),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertTrue($qrCode->isExpiringSoon());
        $this->assertSame(1, $qrCode->expiresInDays());
    }

    public function test_is_expiring_soon_false_outside_warning_window(): void
    {
        $qrCode = $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Later', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(10),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertFalse($qrCode->isExpiringSoon());
    }

    public function test_is_expiring_soon_false_when_no_expiry(): void
    {
        $qrCode = $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'No expiry', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => null,
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertFalse($qrCode->isExpiringSoon());
        $this->assertNull($qrCode->expiresInDays());
    }

    public function test_dispatch_expiring_soon_events_only_for_free_codes_in_window(): void
    {
        Event::fake([QrCodeExpiringSoon::class]);
        Carbon::setTestNow(now());

        // In window, Free -> dispatched.
        $inWindowFree = $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'In window free', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(2),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);
        // In window, but grandfathered Pro -> NOT dispatched (Bestandsschutz).
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'In window pro', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(2),
            'entitlement_snapshot' => ['tier' => 'pro', 'source' => 'stripe'],
        ]);
        // Out of window -> NOT dispatched.
        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Far future', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(20),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $dispatched = QrCode::dispatchExpiringSoonEvents();

        $this->assertSame(1, $dispatched);
        Event::assertDispatched(QrCodeExpiringSoon::class, function (QrCodeExpiringSoon $e) use ($inWindowFree): bool {
            return $e->qrCode->is($inWindowFree);
        });

        Carbon::setTestNow(null);
    }

    public function test_dispatch_expiring_soon_events_skips_already_expired_codes(): void
    {
        Event::fake([QrCodeExpiringSoon::class]);
        Carbon::setTestNow(now());

        $this->createQrCode([
            'user_id' => $this->user->id, 'title' => 'Expired', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->subHour(),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);

        $this->assertSame(0, QrCode::dispatchExpiringSoonEvents());
        Event::assertNotDispatched(QrCodeExpiringSoon::class);

        Carbon::setTestNow(null);
    }

    /**
     * Persist a QR code, setting the immutable entitlement snapshot via direct
     * attribute write (it is not mass-assignable, DEV-592). Accepts the same
     * attribute shape the tests used to pass straight to QrCode::create().
     */
    private function createQrCode(array $attributes): QrCode
    {
        $snapshot = $attributes['entitlement_snapshot'] ?? null;
        unset($attributes['entitlement_snapshot']);

        $qrCode = QrCode::create($attributes);

        if ($snapshot !== null) {
            $qrCode->entitlement_snapshot = $snapshot;
            $qrCode->save();
        }

        return $qrCode;
    }

    private function makeActiveFreeCode(User $user): QrCode
    {
        return $this->createQrCode([
            'user_id' => $user->id, 'title' => 'Active free', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDays(30),
            'entitlement_snapshot' => ['tier' => 'free'],
        ]);
    }
}
