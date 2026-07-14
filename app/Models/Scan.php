<?php

namespace App\Models;

use App\Domain\Analytics\ScanRetention;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Scan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'qr_code_id',
        'qr_code_variant_id',
        'ip_hash',
        'user_agent_raw',
        'user_agent_parsed',
        'referer',
        'accept_language',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'geo_country',
        'geo_region',
        'geo_city',
        'geo_asn',
        'is_bot',
        'device_type',
        'os_family',
        'browser_family',
        'response_type',
        'http_status',
        'response_time_ms',
        'delete_after',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'user_agent_parsed' => 'array',
            'is_bot' => 'boolean',
            'scanned_at' => 'datetime',
            'delete_after' => 'datetime',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /**
     * Populate `delete_after` exactly once when a raw scan is first persisted
     * (Pflichtenheft §3.3.4, P3-T03).
     *
     * Every scan row carries its own retention deadline so the daily purge can
     * delete with a single indexed comparison. An explicitly provided value
     * (backfill, tests) always wins; otherwise the deadline is derived from the
     * owning QR code's entitlement snapshot — Free anchors on the code's
     * creation moment, Pro/Business on the scan's own scanned_at.
     */
    protected static function booted(): void
    {
        static::creating(function (Scan $scan) {
            if ($scan->delete_after !== null) {
                return;
            }

            $qrCode = QrCode::select(['id', 'entitlement_snapshot', 'created_at'])
                ->find($scan->qr_code_id);

            if ($qrCode === null) {
                return;
            }

            $scan->delete_after = app(ScanRetention::class)
                ->deleteAfterFor($qrCode, $scan->scanned_at);
        });
    }

    /**
     * Daily idempotent retention purge (Pflichtenheft §3.3.4, P3-T03).
     *
     * Deletes raw scans whose retention deadline has passed. Idempotent by
     * construction: only rows with `delete_after <= now` are removed, so a
     * repeated run is a no-op and there are no side effects on still-retained
     * data. Rows whose `delete_after` is still NULL (recorded before this
     * feature shipped, or with a missing entitlement snapshot) are backfilled
     * first so the indexed delete covers them too.
     *
     * Aggregated statistics (scan_stats_hourly / scan_stats_daily) live in
     * their own tables keyed on qr_code_id and are never touched here — only
     * raw scans are deleted.
     *
     * @return int Number of raw scans deleted.
     */
    public static function purgeExpiredRetentionPolicy(): int
    {
        $now = now();
        $retention = app(ScanRetention::class);

        // 1. Backfill NULL deadlines so every retained row has an indexable
        //    delete_after (covers legacy rows recorded before this feature).
        static::query()
            ->whereNull('delete_after')
            ->chunkById(500, function ($scans) use ($retention) {
                foreach ($scans as $scan) {
                    $qrCode = QrCode::select(['id', 'entitlement_snapshot', 'created_at'])
                        ->find($scan->qr_code_id);

                    if ($qrCode === null) {
                        // Orphaned scan whose QR code was deleted; the FK
                        // cascade will remove it. Nothing to backfill.
                        continue;
                    }

                    $scan->delete_after = $retention->deleteAfterFor($qrCode, $scan->scanned_at);
                    $scan->saveQuietly();
                }
            });

        // 2. Delete every row whose deadline has passed, in bounded batches so
        //    large tables do not suffer a single long lock.
        $deleted = 0;
        $chunkSize = (int) config('analytics.retention.purge_chunk_size', 1000);

        do {
            $ids = static::query()
                ->whereNotNull('delete_after')
                ->where('delete_after', '<=', $now)
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += (int) static::whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunkSize);

        Log::info('Scan retention purge completed', [
            'deleted' => $deleted,
            'status' => $deleted > 0 ? 'purged' : 'noop',
            'ran_at' => $now->toDateTimeString(),
        ]);

        return $deleted;
    }
}
