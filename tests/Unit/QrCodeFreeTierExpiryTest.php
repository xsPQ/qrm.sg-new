<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\Subscription;
use App\Models\User;
use App\Domain\Entitlement\EntitlementGate;
use App\Services\QrCodeRouteService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QrCodeFreeTierExpiryTest extends TestCase
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

    public function test_free_tier_create_sets_expires_at_to_thirty_days(): void
    {
        Carbon::setTestNow(now());

        $qrCode = $this->service->create($this->user, [
            'title' => 'Free QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertNotNull($qrCode->expires_at);
        $this->assertEqualsWithDelta(
            now()->addDays(30)->getTimestamp(),
            $qrCode->expires_at->getTimestamp(),
            5,
            'Free-tier codes must expire ~30 days after creation.',
        );

        Carbon::setTestNow(null);
    }

    public function test_create_ignores_client_supplied_expires_at_for_free_tier(): void
    {
        Carbon::setTestNow(now());

        $attempted = now()->addDays(365);

        $qrCode = $this->service->create($this->user, [
            'title' => 'Free QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'expires_at' => $attempted,
        ]);

        $this->assertNotEquals(
            $attempted->getTimestamp(),
            $qrCode->expires_at?->getTimestamp(),
            'Client-supplied expires_at must be ignored.',
        );
        $this->assertEqualsWithDelta(
            now()->addDays(30)->getTimestamp(),
            $qrCode->expires_at->getTimestamp(),
            5,
            'Server must compute the 30-day expiry regardless of input.',
        );

        Carbon::setTestNow(null);
    }

    public function test_business_tier_create_has_no_expiry(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'stripe_id' => 'cus_business_1',
            'stripe_status' => 'active',
            'stripe_price' => 'price_business',
            'quantity' => 1,
        ]);

        $qrCode = $this->service->create($this->user, [
            'title' => 'Business QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'expires_at' => now()->addDays(10),
        ]);

        $this->assertNull($qrCode->expires_at, 'Paid tiers must not auto-expire and must ignore client input.');
        $this->assertSame('business', $qrCode->entitlement_snapshot['tier']);
    }

    public function test_update_cannot_change_expires_at(): void
    {
        Carbon::setTestNow(now());

        $qrCode = $this->service->create($this->user, [
            'title' => 'Free QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $originalExpiry = $qrCode->expires_at?->toDateTimeString();
        $this->assertNotNull($originalExpiry);

        $this->service->update($qrCode, [
            'title' => 'Renamed',
            'expires_at' => now()->addYears(5),
        ]);

        $qrCode->refresh();
        $this->assertSame($originalExpiry, $qrCode->expires_at->toDateTimeString(), 'expires_at must be immutable via API.');
        $this->assertSame('Renamed', $qrCode->title, 'Other fields must still update.');

        Carbon::setTestNow(null);
    }

    public function test_cleanup_expired_marks_only_expired_active_codes(): void
    {
        $user = User::factory()->create();

        $expired = QrCode::create([
            'user_id' => $user->id, 'title' => 'Expired', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);
        $stillActive = QrCode::create([
            'user_id' => $user->id, 'title' => 'Future', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);
        $noExpiry = QrCode::create([
            'user_id' => $user->id, 'title' => 'No expiry', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => null,
        ]);
        $alreadyBurned = QrCode::create([
            'user_id' => $user->id, 'title' => 'Burned', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'burned',
            'expires_at' => now()->subDay(),
        ]);

        $affected = QrCode::cleanupExpired();

        $this->assertSame(1, $affected, 'Only the single active+expired code should transition.');
        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $stillActive->fresh()->status);
        $this->assertSame('active', $noExpiry->fresh()->status);
        $this->assertSame('burned', $alreadyBurned->fresh()->status, 'Non-active codes must not be touched.');
    }

    public function test_cleanup_expired_returns_zero_when_nothing_expired(): void
    {
        $user = User::factory()->create();

        QrCode::create([
            'user_id' => $user->id, 'title' => 'Future', 'type' => 'url',
            'content' => ['url' => 'https://example.com'], 'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame(0, QrCode::cleanupExpired());
    }
}
