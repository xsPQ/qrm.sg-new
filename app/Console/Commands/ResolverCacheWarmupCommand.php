<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ResolverCacheWarmer;
use Illuminate\Console\Command;

/**
 * Warm the public resolver cache for the hottest active, unlimited codes
 * (cold start after a deploy or an explicit flush).
 *
 * Pflichtenheft §3.2.3, P3-T05. Renders each eligible code (no scan consumed,
 * no analytics logged) and stores the response under every public slug so the
 * first real scan after a deploy is already a cache hit.
 */
class ResolverCacheWarmupCommand extends Command
{
    protected $signature = 'qr:resolver-cache:warmup
                            {--limit= : Maximum number of codes to warm (default: RESOLVER_CACHE_WARMUP_LIMIT)}';

    protected $description = 'Warm the public resolver cache for the most-scanned active codes';

    public function handle(ResolverCacheWarmer $warmer): int
    {
        $limit = (int) ($this->option('limit') ?? config('qr.resolver_cache.warmup_limit', 100));

        if ($limit <= 0) {
            $limit = (int) config('qr.resolver_cache.warmup_limit', 100);
        }

        $summary = $warmer->warm($limit);

        $this->info(sprintf(
            'Warmed %d code(s) (%d slug(s) stored, %d skipped).',
            $summary['warmed'],
            $summary['slugs'],
            $summary['skipped'],
        ));

        return self::SUCCESS;
    }
}
