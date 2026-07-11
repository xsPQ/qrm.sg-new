<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Command;

/**
 * Delete raw scans past their retention deadline (Pflichtenheft §3.3.4, P3-T03).
 *
 * Idempotent: only scans with `delete_after <= now` are removed, so running it
 * repeatedly (or re-running after a partial failure) is always safe. Aggregated
 * statistics are preserved. Scheduled daily; also runnable on demand.
 */
class PurgeExpiredScansCommand extends Command
{
    /** @var string */
    protected $signature = 'scans:purge-expired';

    /** @var string */
    protected $description = 'Delete raw scans past their retention deadline (Free 60d / Pro+ 24M). Idempotent; preserves aggregated statistics.';

    public function handle(): int
    {
        $deleted = Scan::purgeExpiredRetentionPolicy();

        $this->info("Purged {$deleted} expired scan(s).");

        return self::SUCCESS;
    }
}
