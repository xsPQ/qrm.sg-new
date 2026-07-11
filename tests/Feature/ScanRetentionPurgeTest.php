<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ScanStatsDaily;
use App\Models\ScanStatsHourly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScanRetentionPurgeTest extends TestCase
{
    use RefreshDatabase;

    private function qrCode(string $plan, ?Carbon $createdAt = null): QrCode
    {
        $user = User::factory()->create();

        $qr = new QrCode();
        $qr->user_id = $user->id;
        $qr->title = 'Test QR';
        $qr->type = 'url';
        $qr->content = ['url' => 'https://example.com'];
        $qr->entitlement_snapshot = EntitlementSnapshot::forPlan($plan)->toArray();
        $qr->save();

        if ($createdAt !== null) {
            // Force a historical creation moment (timestamps are otherwise auto).
            $qr->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }

        return $qr->fresh();
    }

    private function recordScan(QrCode $qrCode, ?Carbon $scannedAt = null): Scan
    {
        return Scan::create([
            'qr_code_id' => $qrCode->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => $scannedAt ?? now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 12,
        ]);
    }

    public function test_free_scan_is_deleted_after_60_days_from_qr_creation(): void
    {
        // QR code created 61 days ago → its Free scans are already past the
        // 60-day deadline even if the scan itself was recorded just now.
        $qr = $this->qrCode('free', now()->copy()->subDays(61));
        $this->recordScan($qr);

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, Scan::count());
    }

    public function test_free_scan_within_retention_is_kept(): void
    {
        $qr = $this->qrCode('free', now()->copy()->subDays(10));
        $this->recordScan($qr);

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(0, $deleted);
        $this->assertSame(1, Scan::count());
    }

    public function test_paid_scan_is_deleted_after_24_months_from_scanned_at(): void
    {
        $qr = $this->qrCode('pro');
        $this->recordScan($qr, now()->copy()->subMonths(25));

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, Scan::count());
    }

    public function test_business_scan_within_retention_is_kept(): void
    {
        $qr = $this->qrCode('business');
        $this->recordScan($qr, now()->copy()->subMonths(12));

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(0, $deleted);
        $this->assertSame(1, Scan::count());
    }

    public function test_free_and_paid_scans_use_different_anchors(): void
    {
        // A Free code created long ago (past retention) and a Pro code whose
        // scan is recent: only the Free scan should be purged.
        $freeQr = $this->qrCode('free', now()->copy()->subDays(100));
        $proQr = $this->qrCode('pro');
        $this->recordScan($freeQr);
        $this->recordScan($proQr, now());

        Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(1, Scan::count());
        $this->assertNotNull(Scan::where('qr_code_id', $proQr->id)->first());
    }

    public function test_purge_is_idempotent(): void
    {
        $qr = $this->qrCode('free', now()->copy()->subDays(61));
        $this->recordScan($qr);
        $this->recordScan($qr);

        $first = Scan::purgeExpiredRetentionPolicy();
        $second = Scan::purgeExpiredRetentionPolicy();
        $third = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(2, $first);
        $this->assertSame(0, $second);
        $this->assertSame(0, $third);
        $this->assertSame(0, Scan::count());
    }

    public function test_aggregated_statistics_are_preserved_after_purge(): void
    {
        $qr = $this->qrCode('free', now()->copy()->subDays(61));
        $this->recordScan($qr);

        // Roll-up rows keyed on the QR code — these must survive the purge.
        ScanStatsDaily::create([
            'qr_code_id' => $qr->id,
            'day' => now()->toDateString(),
            'scan_count' => 1,
            'unique_ips' => 1,
            'top_country' => 'DE',
            'top_device' => 'mobile',
        ]);
        ScanStatsHourly::create([
            'qr_code_id' => $qr->id,
            'hour' => now()->startOfHour(),
            'scan_count' => 1,
            'unique_ips' => 1,
        ]);

        Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(0, Scan::count());
        $this->assertSame(1, ScanStatsDaily::count());
        $this->assertSame(1, ScanStatsHourly::count());
    }

    public function test_delete_after_is_populated_on_create_for_free(): void
    {
        $createdAt = now()->copy()->subDays(5);
        $qr = $this->qrCode('free', $createdAt);
        $scan = $this->recordScan($qr);

        // Compare at second granularity: DB timestamp columns drop microseconds.
        $expected = $createdAt->copy()->addDays(60);
        $this->assertEquals($expected->timestamp, $scan->fresh()->delete_after->timestamp);
    }

    public function test_delete_after_is_populated_on_create_for_paid(): void
    {
        $scannedAt = now()->copy()->subMonths(2);
        $qr = $this->qrCode('pro');
        $scan = $this->recordScan($qr, $scannedAt);

        $expected = $scannedAt->copy()->addMonths(24);
        $this->assertEquals($expected->timestamp, $scan->fresh()->delete_after->timestamp);
    }

    public function test_explicit_delete_after_is_respected(): void
    {
        $qr = $this->qrCode('free', now()->copy()->subDays(1));
        $explicit = now()->copy()->addYear();

        $scan = Scan::create([
            'qr_code_id' => $qr->id,
            'scanned_at' => now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 5,
            'delete_after' => $explicit,
        ]);

        $this->assertEquals($explicit->timestamp, $scan->fresh()->delete_after->timestamp);
    }

    public function test_legacy_scan_with_null_delete_after_is_backfilled_and_purged(): void
    {
        // A scan recorded before the feature shipped has no delete_after.
        // Inserted at the DB layer to bypass the model creating hook.
        $qr = $this->qrCode('free', now()->copy()->subDays(61));
        DB::table('scans')->insert([
            'qr_code_id' => $qr->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now(),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 8,
            'delete_after' => null,
            'is_bot' => 0,
        ]);

        $this->assertNull(Scan::first()->delete_after);

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, Scan::count());
    }

    public function test_legacy_scan_with_null_delete_after_is_backfilled_and_kept_when_not_expired(): void
    {
        $qr = $this->qrCode('pro');
        DB::table('scans')->insert([
            'qr_code_id' => $qr->id,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'scanned_at' => now()->subMonths(6),
            'response_type' => 'view',
            'http_status' => 200,
            'response_time_ms' => 8,
            'delete_after' => null,
            'is_bot' => 0,
        ]);

        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->assertSame(0, $deleted);
        $scan = Scan::first();
        $this->assertNotNull($scan);
        $this->assertNotNull($scan->delete_after);
        $this->assertTrue($scan->delete_after->isFuture());
    }
}
