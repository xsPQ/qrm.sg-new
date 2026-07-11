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
 * Roll raw scans for one day up into scan_stats_daily
 * (Pflichtenheft §3.3.2, P3-T02).
 *
 * Like the hourly job, the whole day window is recomputed from the scans table
 * and upserted, so re-running over the same day overwrites instead of
 * accumulating — the idempotency contract. For each QR-code/day the job stores
 * the scan count, the number of distinct ip-hash pseudonyms, and the
 * most-frequent (modal) country, device type, OS family and browser family.
 *
 * "Top" values are resolved per QR-code by grouping value frequencies in PHP
 * (with a deterministic alphabetical tie-break) rather than with a
 * database-specific ordered-set aggregate, so the logic runs unchanged against
 * SQLite (tests) and PostgreSQL.
 */
class AggregateScanStatsDaily implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon  $day  The start (00:00) of the day to aggregate. The job
     *                       processes the half-open interval [day, day+1d).
     */
    public function __construct(
        public Carbon $day,
    ) {
        $this->day = $day->copy()->startOfDay();
    }

    public function handle(): void
    {
        $start = $this->day;
        $end = $this->day->copy()->addDay();

        $aggregates = DB::table('scans')
            ->select('qr_code_id')
            ->selectRaw('COUNT(*) AS scan_count')
            ->selectRaw('COUNT(DISTINCT ip_hash) AS unique_ips')
            ->where('scanned_at', '>=', $start)
            ->where('scanned_at', '<', $end)
            ->groupBy('qr_code_id')
            ->get()
            ->keyBy('qr_code_id');

        if ($aggregates->isEmpty()) {
            return;
        }

        $qrCodeIds = $aggregates->keys()->all();

        $topCountry = $this->topValue('geo_country', $start, $end, $qrCodeIds);
        $topDevice = $this->topValue('device_type', $start, $end, $qrCodeIds);
        $topOs = $this->topValue('os_family', $start, $end, $qrCodeIds);
        $topBrowser = $this->topValue('browser_family', $start, $end, $qrCodeIds);

        $dayString = $start->format('Y-m-d');

        $values = [];
        foreach ($aggregates as $qrCodeId => $aggregate) {
            $values[] = [
                'qr_code_id' => $qrCodeId,
                'day' => $dayString,
                'scan_count' => (int) $aggregate->scan_count,
                'unique_ips' => (int) $aggregate->unique_ips,
                'top_country' => $topCountry[$qrCodeId] ?? null,
                'top_device' => $topDevice[$qrCodeId] ?? null,
                'top_os' => $topOs[$qrCodeId] ?? null,
                'top_browser' => $topBrowser[$qrCodeId] ?? null,
            ];
        }

        DB::table('scan_stats_daily')->upsert(
            $values,
            ['qr_code_id', 'day'],
            ['scan_count', 'unique_ips', 'top_country', 'top_device', 'top_os', 'top_browser'],
        );
    }

    /**
     * Resolve the most frequent non-null value of a column per QR-code within
     * the window. Ties are broken alphabetically so re-runs are deterministic.
     *
     * @param  list<int>  $qrCodeIds
     * @return array<int, string>
     */
    private function topValue(string $column, Carbon $start, Carbon $end, array $qrCodeIds): array
    {
        $rows = DB::table('scans')
            ->select(['qr_code_id', $column])
            ->selectRaw('COUNT(*) AS cnt')
            ->where('scanned_at', '>=', $start)
            ->where('scanned_at', '<', $end)
            ->whereIn('qr_code_id', $qrCodeIds)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy('qr_code_id', $column)
            ->get();

        $top = [];
        foreach ($rows as $row) {
            $value = $row->{$column};
            $current = $top[$row->qr_code_id] ?? null;

            if ($current === null
                || $row->cnt > $current['cnt']
                || ($row->cnt === $current['cnt'] && strcmp($value, $current['value']) < 0)) {
                $top[$row->qr_code_id] = ['cnt' => $row->cnt, 'value' => $value];
            }
        }

        return array_map(fn ($entry) => $entry['value'], $top);
    }
}
