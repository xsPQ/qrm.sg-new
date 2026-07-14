<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\AggregateScanStatsDaily;
use App\Jobs\AggregateScanStatsHourly;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScanStatsAggregationTest extends TestCase
{
    use RefreshDatabase;

    private function createQrCode(): QrCode
    {
        $user = User::factory()->create();

        return QrCode::create([
            'user_id' => $user->id,
            'title' => 'Stats QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function createScan(QrCode $qrCode, array $overrides = []): Scan
    {
        return Scan::create(array_merge([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 10,
        ], $overrides));
    }

    public function test_hourly_job_aggregates_scan_count_and_unique_ips(): void
    {
        $qrCode = $this->createQrCode();
        $hour = Carbon::parse('2026-07-10 09:00');

        // 4 scans inside the hour with 3 distinct ip-hashes.
        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => $hour->copy()->addMinutes(5)]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-b', 'scanned_at' => $hour->copy()->addMinutes(10)]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => $hour->copy()->addMinutes(15)]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-c', 'scanned_at' => $hour->copy()->addMinutes(20)]);

        // Outside the window: previous hour, next-hour boundary, and a later hour.
        $this->createScan($qrCode, ['ip_hash' => 'ip-z', 'scanned_at' => $hour->copy()->subHour()]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-next', 'scanned_at' => $hour->copy()->addHour()]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-boundary', 'scanned_at' => $hour->copy()->addHour()]);

        AggregateScanStatsHourly::dispatchSync($hour);

        $row = DB::table('scan_stats_hourly')
            ->where('qr_code_id', $qrCode->id)
            ->where('hour', $hour->format('Y-m-d H:i:s'))
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(4, (int) $row->scan_count, 'only the 4 in-window scans are counted');
        $this->assertSame(3, (int) $row->unique_ips, 'distinct ip-hashes a/b/c');
        $this->assertSame(1, DB::table('scan_stats_hourly')->count(), 'only the dispatched hour gets a row');
    }

    public function test_hourly_job_is_idempotent_on_rerun(): void
    {
        $qrCode = $this->createQrCode();
        $hour = Carbon::parse('2026-07-10 09:00');

        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => $hour->copy()->addMinutes(5)]);
        $this->createScan($qrCode, ['ip_hash' => 'ip-b', 'scanned_at' => $hour->copy()->addMinutes(10)]);

        AggregateScanStatsHourly::dispatchSync($hour);

        $firstRun = DB::table('scan_stats_hourly')
            ->where('qr_code_id', $qrCode->id)
            ->where('hour', $hour->format('Y-m-d H:i:s'))
            ->first();

        $this->assertSame(2, (int) $firstRun->scan_count);

        // Re-run with no new scans: must NOT double the count.
        AggregateScanStatsHourly::dispatchSync($hour);

        $secondRun = DB::table('scan_stats_hourly')
            ->where('qr_code_id', $qrCode->id)
            ->where('hour', $hour->format('Y-m-d H:i:s'))
            ->first();

        $this->assertSame(2, (int) $secondRun->scan_count, 're-run overwrites, never doubles');

        // A late scan in the same hour is picked up on the next re-run.
        $this->createScan($qrCode, ['ip_hash' => 'ip-c', 'scanned_at' => $hour->copy()->addMinutes(40)]);
        AggregateScanStatsHourly::dispatchSync($hour);

        $thirdRun = DB::table('scan_stats_hourly')
            ->where('qr_code_id', $qrCode->id)
            ->where('hour', $hour->format('Y-m-d H:i:s'))
            ->first();

        $this->assertSame(3, (int) $thirdRun->scan_count);
        $this->assertSame(3, (int) $thirdRun->unique_ips);
    }

    public function test_daily_job_rollup_computes_top_dimensions(): void
    {
        $qrCode = $this->createQrCode();
        $day = Carbon::parse('2026-07-10 00:00');

        $scan = function (string $ip, string $time, array $dims = []) use ($qrCode) {
            $this->createScan($qrCode, array_merge([
                'ip_hash' => $ip,
                'scanned_at' => Carbon::parse($time),
            ], $dims));
        };

        // DE x3, US x1 -> top_country DE.
        $scan('ip-1', '2026-07-10 02:00', ['geo_country' => 'DE', 'device_type' => 'mobile', 'os_family' => 'iOS', 'browser_family' => 'Safari']);
        $scan('ip-1', '2026-07-10 03:00', ['geo_country' => 'DE', 'device_type' => 'mobile', 'os_family' => 'iOS', 'browser_family' => 'Chrome']);
        $scan('ip-2', '2026-07-10 04:00', ['geo_country' => 'DE', 'device_type' => 'desktop', 'os_family' => 'iOS', 'browser_family' => 'Safari']);
        $scan('ip-3', '2026-07-10 05:00', ['geo_country' => 'US', 'device_type' => 'desktop', 'os_family' => 'Android', 'browser_family' => 'Chrome']);

        // Out of window (next day) must be ignored.
        $scan('ip-4', '2026-07-11 01:00', ['geo_country' => 'FR', 'device_type' => 'tablet', 'os_family' => 'Linux', 'browser_family' => 'Firefox']);

        AggregateScanStatsDaily::dispatchSync($day);

        $row = DB::table('scan_stats_daily')
            ->where('qr_code_id', $qrCode->id)
            ->where('day', $day->format('Y-m-d'))
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(4, (int) $row->scan_count);
        $this->assertSame(3, (int) $row->unique_ips, 'distinct ip pseudonyms ip-1/2/3');
        $this->assertSame('DE', $row->top_country, 'DE appears 3x, US 1x');
        $this->assertSame('desktop', $row->top_device, 'mobile 2x, desktop 2x -> tie broken alphabetically: desktop');
        $this->assertSame('iOS', $row->top_os, 'iOS 3x, Android 1x');
        $this->assertSame('Chrome', $row->top_browser, 'Safari 2x, Chrome 2x -> tie broken alphabetically: Chrome');
    }

    public function test_daily_job_handles_tie_alphabetically_and_is_idempotent(): void
    {
        $qrCode = $this->createQrCode();
        $day = Carbon::parse('2026-07-10 00:00');

        // device_type tie: mobile x1, desktop x1 -> alphabetical "desktop".
        $this->createScan($qrCode, ['ip_hash' => 'a', 'scanned_at' => '2026-07-10 10:00', 'device_type' => 'mobile']);
        $this->createScan($qrCode, ['ip_hash' => 'b', 'scanned_at' => '2026-07-10 11:00', 'device_type' => 'desktop']);

        AggregateScanStatsDaily::dispatchSync($day);

        $row = DB::table('scan_stats_daily')
            ->where('qr_code_id', $qrCode->id)
            ->where('day', $day->format('Y-m-d'))
            ->first();

        $this->assertSame('desktop', $row->top_device);

        // Re-run must be stable and not duplicate.
        AggregateScanStatsDaily::dispatchSync($day);

        $this->assertSame(1, DB::table('scan_stats_daily')->where('qr_code_id', $qrCode->id)->count());

        $row = DB::table('scan_stats_daily')
            ->where('qr_code_id', $qrCode->id)
            ->where('day', $day->format('Y-m-d'))
            ->first();

        $this->assertSame(2, (int) $row->scan_count);
        $this->assertSame('desktop', $row->top_device);
    }

    public function test_daily_job_with_no_scans_writes_nothing(): void
    {
        $this->createQrCode();
        $day = Carbon::parse('2026-07-10 00:00');

        AggregateScanStatsDaily::dispatchSync($day);

        $this->assertSame(0, DB::table('scan_stats_daily')->count());
    }

    public function test_hourly_command_dispatches_aggregation(): void
    {
        $qrCode = $this->createQrCode();
        $hour = Carbon::parse('2026-07-10 09:00');

        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => $hour->copy()->addMinutes(5)]);

        $this->artisan('analytics:aggregate-hourly', ['--hour' => '2026-07-10 09:00'])
            ->assertSuccessful();

        $this->assertSame(
            1,
            DB::table('scan_stats_hourly')->where('qr_code_id', $qrCode->id)->count()
        );
    }

    public function test_daily_command_dispatches_aggregation(): void
    {
        $qrCode = $this->createQrCode();

        $this->createScan($qrCode, ['ip_hash' => 'ip-a', 'scanned_at' => '2026-07-10 05:00', 'geo_country' => 'DE']);

        $this->artisan('analytics:aggregate-daily', ['--date' => '2026-07-10'])
            ->assertSuccessful();

        $row = DB::table('scan_stats_daily')
            ->where('qr_code_id', $qrCode->id)
            ->where('day', '2026-07-10')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('DE', $row->top_country);
    }
}
