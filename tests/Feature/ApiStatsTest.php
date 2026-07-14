<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ScanStatsDaily;
use App\Models\ScanStatsHourly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiStatsTest extends TestCase
{
    use RefreshDatabase;

    private function createQrCodeFor(User $user): QrCode
    {
        return QrCode::factory()->for($user)->create();
    }

    private function createScan(QrCode $qrCode, array $attributes = []): Scan
    {
        return Scan::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => 'ip-'.substr(md5((string) fake()->unique()->numberBetween(1, 1000000)), 0, 12),
            'user_agent_raw' => 'Mozilla/5.0',
            'user_agent_parsed' => ['device' => 'Mobile'],
            'referer' => 'https://example.com',
            'accept_language' => 'en-US,en;q=0.9',
            'geo_country' => 'DE',
            'device_type' => 'mobile',
            'os_family' => 'iOS',
            'browser_family' => 'Safari',
            'response_type' => 'redirect',
            'http_status' => 302,
            'response_time_ms' => 12,
            'scanned_at' => now(),
        ], $attributes));
    }

    public function test_stats_returns_scan_data_for_own_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = $this->createQrCodeFor($owner);

        $scan = $this->createScan($qrCode, [
            'geo_country' => 'CH',
            'device_type' => 'desktop',
            'os_family' => 'macOS',
            'browser_family' => 'Chrome',
            'scanned_at' => Carbon::parse('2026-07-10 12:34:56'),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/qr-codes/{$qrCode->id}/stats");

        $response->assertOk();
        $response->assertJsonPath('total_scans', 1);
        $response->assertJsonPath('recent_scans.0.device', 'desktop');
        $response->assertJsonPath('recent_scans.0.os', 'macOS');
        $response->assertJsonPath('recent_scans.0.browser', 'Chrome');
        $response->assertJsonPath('recent_scans.0.country', 'CH');
        $response->assertJsonPath('recent_scans.0.scanned_at', $scan->scanned_at->toISOString());
    }

    public function test_stats_returns_403_for_other_users_qr_code(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $intruder = User::factory()->create(['plan' => 'business']);
        $qrCode = $this->createQrCodeFor($owner);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/qr-codes/{$qrCode->id}/stats")
            ->assertForbidden();
    }

    public function test_daily_stats_returns_aggregated_data(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = $this->createQrCodeFor($owner);
        $day = Carbon::parse('2026-07-10 00:00:00');

        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => '2026-07-10 08:00:00']);
        $this->createScan($qrCode, ['ip_hash' => 'ip-b', 'scanned_at' => '2026-07-10 09:00:00']);
        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => '2026-07-10 10:00:00']);

        ScanStatsDaily::create([
            'qr_code_id' => $qrCode->id,
            'day' => $day->toDateString(),
            'scan_count' => 3,
            'unique_ips' => 2,
            'top_country' => 'DE',
            'top_device' => 'mobile',
            'top_os' => 'iOS',
            'top_browser' => 'Safari',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/qr-codes/{$qrCode->id}/stats/daily");

        $response->assertOk();
        $response->assertJsonPath('daily.0.date', $day->toDateString());
        $response->assertJsonPath('daily.0.count', 3);
        $response->assertJsonPath('daily.0.unique_ips', 2);
    }

    public function test_stats_require_authentication(): void
    {
        $owner = User::factory()->create(['plan' => 'business']);
        $qrCode = $this->createQrCodeFor($owner);

        $this->getJson("/api/qr-codes/{$qrCode->id}/stats")
            ->assertUnauthorized();
    }
}
