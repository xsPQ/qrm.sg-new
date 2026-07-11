<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_code_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertInstanceOf(User::class, $qrCode->user);
        $this->assertEquals($user->id, $qrCode->user->id);
    }

    public function test_qr_code_has_one_route(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertNull($qrCode->route);
    }

    public function test_content_is_casted_to_array(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertIsArray($qrCode->content);
        $this->assertEquals('https://example.com', $qrCode->content['url']);
    }

    public function test_burn_is_casted_to_boolean(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'burn' => true,
        ]);

        $this->assertTrue($qrCode->burn);
    }

    public function test_is_active_returns_true_for_active_qr(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Active QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);

        $this->assertTrue($qrCode->isActive());
    }

    public function test_is_active_returns_false_when_status_is_not_active(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Disabled QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'disabled',
        ]);

        $this->assertFalse($qrCode->isActive());
    }

    public function test_is_expired_returns_true_when_expiry_in_past(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Expired QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($qrCode->isExpired());
    }

    public function test_is_expired_returns_false_when_expiry_in_future(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Future QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertFalse($qrCode->isExpired());
    }

    public function test_is_expired_returns_false_when_no_expiry_set(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'No Expiry',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertFalse($qrCode->isExpired());
    }

    public function test_is_burned_returns_true_when_burn_and_scanned(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Burn QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'burn' => true,
            'scan_count' => 1,
        ]);

        $this->assertTrue($qrCode->isBurned());
    }

    public function test_is_burned_returns_false_when_burn_but_not_scanned(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Burn QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'burn' => true,
            'scan_count' => 0,
        ]);

        $this->assertFalse($qrCode->isBurned());
    }

    public function test_is_burned_returns_false_when_not_burn(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Normal QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'burn' => false,
            'scan_count' => 5,
        ]);

        $this->assertFalse($qrCode->isBurned());
    }

    public function test_has_reached_max_scans_returns_true_when_at_limit(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Limited QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'max_scans' => 3,
            'scan_count' => 3,
        ]);

        $this->assertTrue($qrCode->hasReachedMaxScans());
    }

    public function test_has_reached_max_scans_returns_true_when_over_limit(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Limited QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'max_scans' => 3,
            'scan_count' => 5,
        ]);

        $this->assertTrue($qrCode->hasReachedMaxScans());
    }

    public function test_has_reached_max_scans_returns_false_when_under_limit(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Limited QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'max_scans' => 5,
            'scan_count' => 3,
        ]);

        $this->assertFalse($qrCode->hasReachedMaxScans());
    }

    public function test_has_reached_max_scans_returns_false_when_no_limit(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Unlimited QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $this->assertFalse($qrCode->hasReachedMaxScans());
    }

    public function test_is_active_returns_false_when_expired(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Expired QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($qrCode->isActive());
    }

    public function test_is_active_returns_false_when_max_scans_reached(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Maxed QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'max_scans' => 1,
            'scan_count' => 1,
        ]);

        $this->assertFalse($qrCode->isActive());
    }

    public function test_soft_deletes_works(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Deleted QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        $qrCode->delete();

        $this->assertSoftDeleted($qrCode);
    }
}
