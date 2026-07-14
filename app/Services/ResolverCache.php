<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use Illuminate\Cache\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Redis-backed response cache for the public QR-code resolver
 * (Pflichtenheft §3.2.3, P3-T05).
 *
 * A warm cache lets the resolver serve the previously rendered response
 * straight from Redis without any DB roundtrip (target p95 < 50 ms cached).
 * Only unlimited, non-password, active codes are cacheable; burn / max_scans /
 * password / expired codes always run the live, concurrency-safe path and are
 * never stored here.
 *
 * Key/index model (portable across the Redis and array stores — no cache tags,
 * which would break lookup-by-slug + invalidate-by-code):
 *  - Lookup entry: `lookup:{host}:{slug}` -> cached payload. Looked up by slug
 *    (the resolver does not know the owning code on a hit), so it is stored
 *    under the plain slug key with no tag namespacing.
 *  - Per-code index: `index:qrcode:{id}` -> list of lookup keys owned by that
 *    code. Lets {@see forgetFor()} invalidate every slug (code + alias, any
 *    host) for one code in O(keys).
 *  - Global index: `index:all` -> list of every lookup key, for {@see flush()}.
 *
 * Consistency: every entry carries a TTL bounded by the code's expires_at, so
 * a cached entry can never outlive its code (§3.2 #7), plus an explicit default
 * TTL. Per-code invalidation fires from the QrCode saved/deleted lifecycle
 * hooks on update, delete, deactivation, burn/max_scans completion and password
 * change. The scan counter & analytics for a cache hit run asynchronously
 * (§3.2 #8), so the response path stays DB-free.
 */
class ResolverCache
{
    /** Cache payload schema version (bump when the stored shape changes). */
    public const VERSION = 1;

    public const KEY_LOOKUP = 'lookup:';

    public const KEY_INDEX_QRCODE = 'index:qrcode:';

    public const KEY_INDEX_ALL = 'index:all';

    /**
     * Build the lookup key for a (host, slug). Slug is normalised
     * case-insensitively exactly like the route service resolves it.
     */
    public function key(string $host, string $slug): string
    {
        return self::KEY_LOOKUP . strtolower($host) . ':' . strtolower($slug);
    }

    /**
     * Whether the resolver cache is enabled (feature flag). When disabled the
     * controller falls back to the original, uncached resolution path.
     */
    public function enabled(): bool
    {
        return (bool) config('qr.resolver_cache.enabled', true);
    }

    /**
     * Look up a cached payload for (host, slug). Returns null on miss or when
     * the cache is disabled. Records hit/miss + latency metrics.
     *
     * @return array<string,mixed>|null
     */
    public function get(string $host, string $slug): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $start = microtime(true);

        try {
            $payload = $this->store()->get($this->key($host, $slug));
        } catch (Throwable $e) {
            $this->metric('error', $start);
            Log::warning('Resolver cache read failed: ' . $e->getMessage());

            return null;
        }

        $this->metric($payload !== null ? 'hit' : 'miss', $start);

        if (! is_array($payload) || ($payload['v'] ?? null) !== self::VERSION) {
            return null;
        }

        return $payload;
    }

    /**
     * Store a rendered response for (host, slug). The TTL is bounded by the
     * code's expires_at so the entry never outlives the code; entries for codes
     * without expiry use the configured default TTL. The lookup key is also
     * recorded in the per-code and global indices for targeted invalidation.
     *
     * @param  array<string,mixed>  $payload  from {@see captureResponse()}
     */
    public function put(string $host, string $slug, int $qrCodeId, array $payload, ?Carbon $expiresAt): void
    {
        if (! $this->enabled()) {
            return;
        }

        $ttl = $this->ttlFor($expiresAt);
        $payload['v'] = self::VERSION;
        $payload['qr_code_id'] = $qrCodeId;
        $lookupKey = $this->key($host, $slug);

        try {
            $store = $this->store();
            $store->put($lookupKey, $payload, $ttl);

            $this->indexAdd(self::KEY_INDEX_QRCODE . $qrCodeId, $lookupKey, $ttl);
            $this->indexAdd(self::KEY_INDEX_ALL, $lookupKey, $ttl);
        } catch (Throwable $e) {
            // A failed write must never break serving the response.
            Log::warning('Resolver cache write failed: ' . $e->getMessage());
        }
    }

    /**
     * Whether a code is eligible to have its response cached. Burn, max_scans,
     * password-protected, expired or inactive codes are excluded: they either
     * require a live atomic consume per request or must never be served from a
     * shared cache.
     */
    public function isCacheable(QrCode $qrCode): bool
    {
        return $qrCode->status === 'active'
            && ! $qrCode->burn
            && $qrCode->max_scans === null
            && empty($qrCode->password_hash)
            && ! $qrCode->isExpired();
    }

    /**
     * Capture the HTTP-level representation of a final response so it can be
     * stored and later rebuilt verbatim by {@see toResponse()}. Returns null
     * when the response cannot be safely serialised (e.g. binary/empty body),
     * which the caller treats as "not cacheable".
     *
     * @return array<string,mixed>|null
     */
    public function captureResponse(SymfonyResponse $response): ?array
    {
        $body = $response->getContent();

        if ($body === false || $body === null) {
            return null;
        }

        return [
            'status' => $response->getStatusCode(),
            'body' => $body,
            'content_type' => $response->headers->get('Content-Type', 'text/html'),
            'location' => $response->headers->get('Location'),
        ];
    }

    /**
     * Rebuild a Symfony Response from a stored payload. Security headers are
     * applied afterwards by the controller, identical to the uncached path.
     */
    public function toResponse(array $payload): Response
    {
        $headers = [];

        if (! empty($payload['content_type'])) {
            $headers['Content-Type'] = $payload['content_type'];
        }

        if (! empty($payload['location'])) {
            $headers['Location'] = $payload['location'];
        }

        return new Response((string) $payload['body'], (int) $payload['status'], $headers);
    }

    /**
     * Invalidate every cached entry owned by a QR code (code + alias, any
     * host). Called from the QrCode saved/deleted lifecycle hooks on update,
     * delete, deactivation, burn/max_scans completion and password change.
     * Uses the per-code index so all slugs for the code are cleared without a
     * route lookup.
     */
    public function forgetFor(QrCode $qrCode): void
    {
        if (! $this->enabled()) {
            return;
        }

        $id = (int) $qrCode->id;

        try {
            $store = $this->store();
            $indexKey = self::KEY_INDEX_QRCODE . $id;
            $keys = $this->indexRead($indexKey);

            foreach ($keys as $lookupKey) {
                $store->forget($lookupKey);
            }

            $store->forget($indexKey);

            // Also drop any global-index references to keep that index tidy.
            $this->indexRemove(self::KEY_INDEX_ALL, $keys);
        } catch (Throwable $e) {
            Log::warning('Resolver cache invalidation failed: ' . $e->getMessage());
        }
    }

    /**
     * Flush the entire resolver namespace. Used by the deploy/cold-start
     * command and by tests.
     */
    public function flush(): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $store = $this->store();
            $keys = $this->indexRead(self::KEY_INDEX_ALL);

            foreach ($keys as $lookupKey) {
                $store->forget($lookupKey);
            }

            // Clear any remaining per-code indices.
            $store->forget(self::KEY_INDEX_ALL);
        } catch (Throwable $e) {
            Log::warning('Resolver cache flush failed: ' . $e->getMessage());
        }
    }

    /**
     * Read the current hit/miss counters (best-effort; absent when the metrics
     * store is unavailable). Used by observability and the warmup command.
     *
     * @return array<string,int>
     */
    public function metrics(): array
    {
        if (! $this->enabled() || ! $this->metricsEnabled()) {
            return ['hits' => 0, 'misses' => 0, 'errors' => 0];
        }

        try {
            $store = $this->store();

            return [
                'hits' => (int) $store->get('metrics:hits', 0),
                'misses' => (int) $store->get('metrics:misses', 0),
                'errors' => (int) $store->get('metrics:errors', 0),
            ];
        } catch (Throwable) {
            return ['hits' => 0, 'misses' => 0, 'errors' => 0];
        }
    }

    /**
     * Resolve the TTL (seconds) for a new entry. Bounded by the code's
     * expires_at when set so the entry can never outlive its code, capped by
     * the configured default TTL.
     */
    public function ttlFor(?Carbon $expiresAt): int
    {
        $default = (int) config('qr.resolver_cache.ttl', 3600);
        $seconds = $default;

        if ($expiresAt !== null) {
            $untilExpiry = (int) now()->diffInSeconds($expiresAt, absolute: false);

            if ($untilExpiry > 0) {
                $seconds = min($default, $untilExpiry);
            } else {
                // Already expired — store the shortest possible entry so the
                // next request re-evaluates on the live path.
                $seconds = 1;
            }
        }

        return max(1, $seconds);
    }

    /**
     * The configured cache store backing the resolver namespace.
     */
    protected function store(): Repository
    {
        return Cache::store((string) config('qr.resolver_cache.store', 'resolver'));
    }

    protected function metricsEnabled(): bool
    {
        return (bool) config('qr.resolver_cache.metrics', true);
    }

    /**
     * Append a lookup key to a list index, de-duplicated. The index inherits
     * the entry TTL so stale index entries self-expire alongside their keys.
     */
    protected function indexAdd(string $indexKey, string $lookupKey, int $ttl): void
    {
        $keys = $this->indexRead($indexKey);

        if (in_array($lookupKey, $keys, true)) {
            return;
        }

        $keys[] = $lookupKey;

        $this->store()->put($indexKey, $keys, $ttl);
    }

    /**
     * @return list<string>
     */
    protected function indexRead(string $indexKey): array
    {
        $keys = $this->store()->get($indexKey, []);

        return is_array($keys) ? array_values(array_map('strval', $keys)) : [];
    }

    /**
     * Remove a set of lookup keys from a list index.
     *
     * @param  list<string>  $lookupKeys
     */
    protected function indexRemove(string $indexKey, array $lookupKeys): void
    {
        if (empty($lookupKeys)) {
            return;
        }

        $keys = $this->indexRead($indexKey);

        if (empty($keys)) {
            return;
        }

        $remaining = array_values(array_diff($keys, $lookupKeys));

        if (count($remaining) === count($keys)) {
            return;
        }

        if (empty($remaining)) {
            $this->store()->forget($indexKey);
        } else {
            // Preserve a reasonable TTL on the trimmed index.
            $this->store()->put($indexKey, $remaining, (int) config('qr.resolver_cache.ttl', 3600));
        }
    }

    /**
     * Record a hit/miss/error counter plus a latency sample. Counters live in
     * the same dedicated store and never throw into the response path.
     */
    protected function metric(string $kind, float $start): void
    {
        if (! $this->enabled() || ! $this->metricsEnabled()) {
            return;
        }

        $elapsed = (int) round((microtime(true) - $start) * 1000);

        $counterKey = match ($kind) {
            'hit' => 'metrics:hits',
            'miss' => 'metrics:misses',
            default => 'metrics:errors',
        };

        try {
            $this->store()->increment($counterKey);

            Log::debug(sprintf('resolver.cache %s time=%dms', $kind, $elapsed));
        } catch (Throwable) {
            // Metrics are best-effort; never surface into the response path.
        }
    }
}
