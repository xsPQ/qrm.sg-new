<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate raw scans for one hour into scan_stats_hourly
 * (Pflichtenheft §3.3.2, P3-T02).
 *
 * The job re-computes the whole hour window from the scans table and upserts
 * (insert-or-replace) the result, so a re-run over the same hour overwrites the
 * previously stored value instead of adding to it — that is the idempotency
 * contract: "Re-Run überschreibt/füllt Fenster ohne Doppelzählung". Because only
 * one bounded hour is ever processed per dispatch, the aggregation is
 * incremental across time while still cheap enough to recompute a single window
 * from scratch, and the scans table time index drives the window lookup.
 *
 * The query is deliberately written with portable SQL (COUNT, COUNT(DISTINCT),
 * half-open range) so it runs unchanged against SQLite (tests) and PostgreSQL.
 */
class AggregateScanStatsHourly implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon  $hour  The start of the hour window to aggregate. The job
     *                        processes the half-open interval [hour, hour+1h).
     */
    public function __construct(
        public Carbon $hour,
    ) {
        $this->hour = $hour->copy()->startOfHour();
    }

    public function handle(): void
    {
        $start = $this->hour;
        $end = $this->hour->copy()->addHour();

        $rows = DB::table('scans')
            ->select('qr_code_id')
            ->selectRaw('COUNT(*) AS scan_count')
            ->selectRaw('COUNT(DISTINCT ip_hash) AS unique_ips')
            ->where('scanned_at', '>=', $start)
            ->where('scanned_at', '<', $end)
            ->groupBy('qr_code_id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $hourString = $start->format('Y-m-d H:i:s');

        $values = $rows->map(fn ($row) => [
            'qr_code_id' => $row->qr_code_id,
            'hour' => $hourString,
            'scan_count' => (int) $row->scan_count,
            'unique_ips' => (int) $row->unique_ips,
        ])->all();

        DB::table('scan_stats_hourly')->upsert(
            $values,
            ['qr_code_id', 'hour'],
            ['scan_count', 'unique_ips'],
        );
    }
}
