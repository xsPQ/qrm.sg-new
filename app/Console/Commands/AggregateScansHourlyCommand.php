<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AggregateScanStatsHourly;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateScansHourlyCommand extends Command
{
    protected $signature = 'analytics:aggregate-hourly
                            {--hour= : The hour to aggregate as Y-m-d H:00 (defaults to the previous completed hour)}';

    protected $description = 'Aggregate raw scans for one hour into scan_stats_hourly (P3-T02). Idempotent and safe to re-run.';

    public function handle(): int
    {
        $hour = $this->option('hour')
            ? Carbon::parse((string) $this->option('hour'))->startOfHour()
            : Carbon::now()->startOfHour()->subHour();

        AggregateScanStatsHourly::dispatch($hour);

        $this->info("Dispatched hourly scan aggregation for {$hour->format('Y-m-d H:i')}.");

        return self::SUCCESS;
    }
}
