<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\CleanupGuestQrCodes;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guest QR cleanup + transfer tests (FEAT-03b/c).
 */
class GuestQrCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_job_deletes_expired_guest_codes(): void
    {
        $guestUser = User::create([
            'name' => 'Guest',
            'email' => 'guest_test@anonymous.qrm.sg',
            'password' => bcrypt('test'),
            'email_verified_at' => now(),
        ]);

        $expiredQr = QrCode::create([
            'user_id' => $guestUser->id,
            'title' => 'Expired Guest',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->subHour(),
        ]);

        $normalQr = QrCode::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Normal User QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->subHour(),
        ]);

        CleanupGuestQrCodes::dispatch();

        $this->assertDatabaseMissing('qr_codes', ['id' => $expiredQr->id]);
        $this->assertDatabaseHas('qr_codes', ['id' => $normalQr->id]);
    }

    public function test_cleanup_job_keeps_non_expired_guest_codes(): void
    {
        $guestUser = User::create([
            'name' => 'Guest',
            'email' => 'guest_active@anonymous.qrm.sg',
            'password' => bcrypt('test'),
            'email_verified_at' => now(),
        ]);

        $activeQr = QrCode::create([
            'user_id' => $guestUser->id,
            'title' => 'Active Guest',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->addHours(12),
        ]);

        CleanupGuestQrCodes::dispatch();

        $this->assertDatabaseHas('qr_codes', ['id' => $activeQr->id]);
    }
}
