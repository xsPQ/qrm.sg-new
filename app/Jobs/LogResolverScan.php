<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ScanRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Record a resolver scan asynchronously for a cache hit (Pflichtenheft §3.2 #8).
 *
 * A warm resolver cache serves the rendered response without a DB roundtrip;
 * scan counting and analytics are dispatched to this job so the response path
 * stays fast. Only cacheable (unlimited) codes ever hit the cache, so this job
 * only ever increments the scan counter of an active, unlimited code — never
 * the burn/max_scans atomic consume, which always runs on the live path.
 *
 * The job is idempotent for its single responsibility: it records exactly one
 * scan and one counter increment per dispatch. With the `sync` queue driver
 * (test suite) it runs inline, preserving the synchronous behaviour the
 * feature tests assert on. The scan row is built through {@see ScanRecorder},
 * the same service the live path uses, so a hit-recorded scan is identical to a
 * miss-recorded one.
 */
class LogResolverScan implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string,mixed>  $request  serialisable request capture (ScanAttributes::requestCapture)
     * @param  array{response_type:string,http_status:int,response_time_ms:int}  $response
     */
    public function __construct(
        public int $qrCodeId,
        public array $request,
        public array $response,
    ) {}

    public function handle(ScanRecorder $recorder): void
    {
        // Conditional increment: only while the code is still active, mirroring
        // the live resolver's atomic guard. A code that has since been
        // deactivated/burned/expired is left untouched (no late increment).
        DB::update(
            'UPDATE qr_codes
                SET scan_count = scan_count + 1,
                    updated_at = ?
                WHERE id = ? AND status = ?',
            [now(), $this->qrCodeId, 'active'],
        );

        try {
            $recorder->record($this->qrCodeId, $this->request, $this->response);
        } catch (Throwable $e) {
            // A failed analytics insert must not crash the worker; the counter
            // increment above already accounted for the scan.
            Log::warning('Resolver scan analytics insert failed: ' . $e->getMessage());
        }
    }
}
