<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ResolverCache;
use Illuminate\Console\Command;

/**
 * Flush the entire public resolver cache (cold start / deploy / debugging).
 *
 * Pflichtenheft §3.2.3, P3-T05. Per-code invalidation happens automatically on
 * every mutation via the QrCode lifecycle hooks; this command clears the whole
 * namespace at once — e.g. after a deploy that changes rendering, or when
 * rotating the cache store.
 */
class ResolverCacheFlushCommand extends Command
{
    protected $signature = 'qr:resolver-cache:flush';

    protected $description = 'Flush the entire public resolver cache (cold start)';

    public function handle(ResolverCache $cache): int
    {
        if (! $cache->enabled()) {
            $this->warn('Resolver cache is disabled (RESOLVER_CACHE_ENABLED=false). Nothing to flush.');

            return self::SUCCESS;
        }

        $cache->flush();

        $this->info('Resolver cache flushed.');

        return self::SUCCESS;
    }
}
