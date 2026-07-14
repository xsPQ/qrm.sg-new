<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Analytics\ScanRetention;
use App\Domain\Entitlement\EntitlementSnapshot;
use App\Models\QrCode;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScanRetentionTest extends TestCase
{
    private ScanRetention $retention;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retention = new ScanRetention();
    }

    private function qrCode(string $plan, Carbon $createdAt): QrCode
    {
        $qr = new QrCode();
        $qr->entitlement_snapshot = EntitlementSnapshot::forPlan($plan)->toArray();
        $qr->created_at = $createdAt;

        return $qr;
    }

    public function test_free_scan_deadline_anchors_on_qr_code_creation_plus_60_days(): void
    {
        $createdAt = Carbon::parse('2026-01-01 10:00:00');
        $qrCode = $this->qrCode('free', $createdAt);

        // The scan was recorded moments ago, but Free retention ignores
        // scanned_at entirely and anchors on the QR code's creation moment.
        $deadline = $this->retention->deleteAfterFor($qrCode, Carbon::now());

        $this->assertEquals($createdAt->copy()->addDays(60), $deadline);
    }

    public function test_free_scan_deadline_ignores_scanned_at(): void
    {
        $createdAt = Carbon::parse('2026-01-01');
        $qrCode = $this->qrCode('free', $createdAt);

        $recent = $this->retention->deleteAfterFor($qrCode, Carbon::now());
        $old = $this->retention->deleteAfterFor($qrCode, Carbon::parse('2020-01-01'));

        // Both share the same deadline regardless of when the scan happened.
        $this->assertEquals($recent, $old);
    }

    public function test_pro_scan_deadline_anchors_on_scanned_at_plus_24_months(): void
    {
        $scannedAt = Carbon::parse('2024-06-15 12:00:00');
        $qrCode = $this->qrCode('pro', Carbon::parse('2024-01-01'));

        $deadline = $this->retention->deleteAfterFor($qrCode, $scannedAt);

        $this->assertEquals($scannedAt->copy()->addMonths(24), $deadline);
    }

    public function test_business_scan_deadline_anchors_on_scanned_at_plus_24_months(): void
    {
        $scannedAt = Carbon::parse('2024-06-15');
        $qrCode = $this->qrCode('business', Carbon::parse('2024-01-01'));

        $deadline = $this->retention->deleteAfterFor($qrCode, $scannedAt);

        $this->assertEquals($scannedAt->copy()->addMonths(24), $deadline);
    }

    public function test_paid_deadline_defaults_scanned_at_to_now(): void
    {
        Carbon::setTestNow('2026-07-10 09:00:00');
        $qrCode = $this->qrCode('pro', Carbon::parse('2024-01-01'));

        try {
            $deadline = $this->retention->deleteAfterFor($qrCode);
            $this->assertEquals(Carbon::now()->copy()->addMonths(24), $deadline);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_free_deadline_honours_configured_days(): void
    {
        config()->set('analytics.retention.free_days', 90);

        $createdAt = Carbon::parse('2026-01-01');
        $qrCode = $this->qrCode('free', $createdAt);

        $this->assertEquals($createdAt->copy()->addDays(90), $this->retention->deleteAfterFor($qrCode));
    }

    public function test_paid_deadline_honours_configured_months(): void
    {
        config()->set('analytics.retention.paid_months', 36);

        $scannedAt = Carbon::parse('2026-01-01');
        $qrCode = $this->qrCode('business', Carbon::parse('2025-01-01'));

        $this->assertEquals($scannedAt->copy()->addMonths(36), $this->retention->deleteAfterFor($qrCode, $scannedAt));
    }
}
