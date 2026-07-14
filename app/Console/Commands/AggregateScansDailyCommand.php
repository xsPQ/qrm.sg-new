<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AggregateScanStatsDaily;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateScansDailyCommand extends Command
{
    protected $signature = 'analytics:aggregate-daily
                            {--date= : The day to aggregate as Y-m-d (defaults to yesterday)}';

    protected $description = 'Roll raw scans for one day up into scan_stats_daily (P3-T02). Idempotent and safe to re-run.';

    public function handle(): int
    {
        $day = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay()->subDay();

        AggregateScanStatsDaily::dispatch($day);

        $this->info("Dispatched daily scan aggregation for {$day->format('Y-m-d')}.");

        return self::SUCCESS;
    }
}
