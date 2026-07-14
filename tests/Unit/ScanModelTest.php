<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanModelTest extends TestCase
{
    use RefreshDatabase;

    private function createScan(array $attributes = []): Scan
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        return Scan::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 10,
        ], $attributes));
    }

    public function test_scan_belongs_to_qr_code(): void
    {
        $scan = $this->createScan();

        $this->assertInstanceOf(QrCode::class, $scan->qrCode);
    }

    public function test_scan_casts_is_bot_to_boolean(): void
    {
        $scan = $this->createScan(['is_bot' => true]);

        $this->assertTrue($scan->is_bot);
    }

    public function test_scan_casts_scanned_at_to_datetime(): void
    {
        $scan = $this->createScan(['scanned_at' => '2026-01-15 10:30:00']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $scan->scanned_at);
    }

    public function test_scan_casts_delete_after_to_datetime(): void
    {
        $scan = $this->createScan(['delete_after' => now()->addDays(90)]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $scan->delete_after);
    }

    public function test_scan_has_fillable_attributes(): void
    {
        $scan = $this->createScan([
            'user_agent_raw' => 'Mozilla/5.0',
            'referer' => 'https://referrer.example.com',
            'utm_source' => 'facebook',
            'utm_medium' => 'social',
            'utm_campaign' => 'summer2026',
            'geo_country' => 'DE',
            'device_type' => 'mobile',
            'os_family' => 'iOS',
            'browser_family' => 'Safari',
            'response_type' => 'redirect',
            'http_status' => 302,
            'response_time_ms' => 150,
        ]);

        $this->assertEquals('Mozilla/5.0', $scan->user_agent_raw);
        $this->assertEquals('https://referrer.example.com', $scan->referer);
        $this->assertEquals('facebook', $scan->utm_source);
        $this->assertEquals('social', $scan->utm_medium);
        $this->assertEquals('summer2026', $scan->utm_campaign);
        $this->assertEquals('DE', $scan->geo_country);
        $this->assertEquals('mobile', $scan->device_type);
        $this->assertEquals('iOS', $scan->os_family);
        $this->assertEquals('Safari', $scan->browser_family);
        $this->assertEquals('redirect', $scan->response_type);
        $this->assertEquals(302, $scan->http_status);
        $this->assertEquals(150, $scan->response_time_ms);
    }

    public function test_qr_code_has_many_scans(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
        ]);

        Scan::create([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 10,
        ]);
        Scan::create([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now()->addMinute(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 10,
        ]);

        $this->assertCount(2, $qrCode->fresh()->scans);
    }

    public function test_timestamps_are_disabled(): void
    {
        $scan = $this->createScan();

        $this->assertFalse($scan->timestamps);
    }
}
