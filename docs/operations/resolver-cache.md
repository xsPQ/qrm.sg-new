# Resolver Cache — Operations (Pflichtenheft §3.2.3, P3-T05)

The public QR-code resolver (`GET /{codeOrAlias}`) is the application hot path.
Its rendered response for **unlimited, non-password, active** codes is cached in
a dedicated Redis store so a warm cache serves the scan with no database
roundtrip (target p95 < 50 ms cached, < 150 ms uncached). Burn / `max_scans` /
password / expired codes are never cached — they always run the live,
concurrency-safe atomic consume.

## Configuration

| Env var | Default | Purpose |
|---|---|---|
| `RESOLVER_CACHE_ENABLED` | `true` | Feature flag. `false` → pass-through (no caching). |
| `RESOLVER_CACHE_STORE` | `resolver` | Cache store name (defined in `config/cache.php`, Redis). Use `array` where Redis is unavailable. |
| `RESOLVER_CACHE_TTL` | `3600` | Hard ceiling on entry lifetime, in seconds. |
| `RESOLVER_CACHE_WARMUP_LIMIT` | `100` | Default number of codes warmed by the warmup command. |
| `RESOLVER_CACHE_METRICS` | `true` | Record hit/miss/error counters + latency log lines. |

## Cold start / deploy

After a deploy that changes rendering, or after rotating the store, flush and
warm the cache so the hottest codes are served from cache immediately:

```bash
php artisan qr:resolver-cache:flush         # clear the whole namespace
php artisan qr:resolver-cache:warmup        # render+store the top active unlimited codes
php artisan qr:resolver-cache:warmup --limit=200   # warm more
```

Warmup renders each eligible code through the same type handlers as a live
resolve **without** consuming a scan or logging analytics, and stores the
response under every public slug (system code + custom alias). Without warmup
the first scan after a deploy is a normal cache miss, then cached — the cache
self-warms on demand.

Run the warmup as a deploy hook (one-shot), not on a schedule.

## Consistency / invalidation

Cached entries are invalidated automatically on every mutation via the `QrCode`
`saved`/`deleted` lifecycle hooks:

- edit (content, title, settings, burn, max_scans, password)
- delete (soft-delete)
- deactivation / reactivation (Filament admin `setStatus`)
- burn / `max_scans` completion during a live resolve (explicit in `QrCodeResolver`)

Expiry by date is additionally guaranteed by the **TTL being bounded by
`expires_at`**: a cached entry can never outlive its code, and once expired the
live path returns 410 (`isActive()` honours `isExpired()` regardless of status).

Scan counting and analytics for a cache hit run **asynchronously** over the
queue (Pflichtenheft §3.2 #8); a cacheable hit therefore never performs a
synchronous database write on the response path. With the `sync` queue driver
(test suite) the scan job runs inline, keeping counting observable there.

## Observability

Hit/miss/error counters are kept in the resolver store under `metrics:hits`,
`metrics:misses`, `metrics:errors`, and every lookup emits a
`resolver.cache {hit|miss|error} time=Xms` debug log line. p95 latency is
measured directly by the resolver load test (acceptance: cached < 50 ms,
uncached < 150 ms).
