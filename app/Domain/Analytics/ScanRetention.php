<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Models\QrCode;
use Illuminate\Support\Carbon;

/**
 * Computes the raw-scan retention deadline (Pflichtenheft §3.3.4, P3-T03).
 *
 * Free-entitlement QR codes: their raw scans are purged a fixed number of
 * days after the QR CODE was created — not after the individual scan — so
 * every scan of a Free code shares the same deadline. Pro/Business codes:
 * each raw scan is purged a fixed number of months after it was recorded
 * (`scanned_at`).
 *
 * Pure calculation only — no database access, no side effects. Both the
 * per-scan `delete_after` population (Scan model) and the scheduled purge
 * delegate here so the two paths can never disagree about a deadline.
 */
final class ScanRetention
{
    /**
     * The deadline after which a raw scan must be deleted.
     *
     * @param  Carbon|null  $scannedAt  When the scan was recorded; defaults to
     *     now for paid codes (unused for Free codes, which anchor on the QR
     *     code's creation moment).
     */
    public function deleteAfterFor(QrCode $qrCode, ?Carbon $scannedAt = null): Carbon
    {
        if ($qrCode->entitlementSnapshot()->isFree()) {
            $days = (int) config('analytics.retention.free_days', 60);

            return $qrCode->created_at->copy()->addDays($days);
        }

        $months = (int) config('analytics.retention.paid_months', 24);

        return ($scannedAt ?? Carbon::now())->copy()->addMonths($months);
    }
}
