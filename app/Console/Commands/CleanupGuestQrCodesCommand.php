<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CleanupGuestQrCodes;
use Illuminate\Console\Command;

/**
 * Dispatch the guest QR-code cleanup job (M5-T03b).
 *
 * Deletes anonymous (guest) QR codes past their 24h expiry. Scheduled daily;
 * also runnable on demand. Delegates the actual work to {@see CleanupGuestQrCodes}.
 *
 * The job is dispatched synchronously (dispatchSync) so that the command can
 * report the exact number of codes deleted in its output. The underlying job
 * is idempotent and safe to re-run.
 */
class CleanupGuestQrCodesCommand extends Command
{
    /** @var string */
    protected $signature = 'guest-qr-codes:cleanup';

    /** @var string */
    protected $description = 'Delete expired anonymous (guest) QR codes past their 24h expiry. Idempotent; does not affect registered-user codes.';

    public function handle(): int
    {
        // Call the job handler directly (not via dispatchSync) so we get the
        // return value (count of deleted codes). The job itself is lightweight
        // and runs synchronously here.
        $deleted = app(CleanupGuestQrCodes::class)->handle();

        $this->info("Deleted {$deleted} expired guest QR code(s).");

        return self::SUCCESS;
    }
}
