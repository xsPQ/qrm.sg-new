<?php

declare(strict_types=1);

/*
 * qrm.sg QR-code plan rules (Pflichtenheft §2.1–§2.4).
 *
 * These values drive the server-side Free-Tier enforcement: the maximum number
 * of active Free QR-codes, the immutable 30-day Free expiry, and the 3-day
 * pre-expiry warning window that feeds the Ablauf-Prompt and the extensible
 * expiry-warning email hook (P2-T12).
 */
return [

    // Free-Tier rules (Pflichtenheft §2.1, §2.4).
    'free' => [
        // Maximum number of active (non-expired, non-burned, non-maxed) QR-codes
        // a Free user may own. Grandfathered Pro/Business resources never count
        // against this limit (Bestandsschutz, §3.5.3).
        'max_active_qr_codes' => env('QR_FREE_MAX_ACTIVE', 10),

        // Immutable server-side expiry applied to every Free QR-code at creation.
        'expiry_days' => env('QR_FREE_EXPIRY_DAYS', 30),

        // How many days before expires_at the Ablauf-Prompt/badge and the
        // expiry-warning email hook fire.
        'expiry_warning_days' => env('QR_FREE_EXPIRY_WARNING_DAYS', 3),
    ],

    // Download profile caps (Pflichtenheft §2.1/§2.2, P2-T07). Driven by the
    // per-code entitlement snapshot: Free (`standard`) is capped at a standard
    // resolution; Pro/Business (`high_resolution`) use the full size.
    'download' => [
        'max_size_standard' => env('QR_DOWNLOAD_MAX_SIZE_STANDARD', 512),
        'max_size_high_res' => env('QR_DOWNLOAD_MAX_SIZE_HIGH_RES', 2000),
    ],

    // Public resolver Redis cache (Pflichtenheft §3.2.3, P3-T05).
    //
    // The resolver is the application hot path (one DB-heavy resolution per
    // scan). Unlimited, non-password codes are cached so a warm cache serves
    // the rendered response straight from Redis without a DB roundtrip
    // (target p95 < 50 ms cached, < 150 ms uncached). Burn / max_scans /
    // password / expired codes are never cached — they always run the live,
    // concurrency-safe atomic path.
    //
    // Correctness is maintained by:
    //  - TTL bounded by the code's expires_at (an entry never outlives its
    //    code, §3.2 #7), plus an explicit default TTL as a hard ceiling;
    //  - tag-based invalidation on every mutating event (update, delete,
    //    deactivation, burn/max_scans completion, password change) via the
    //    QrCode model saved/deleted hooks (see QrCodeObserver);
    //  - asynchronous scan counting & analytics on a cache hit (the scan
    //    event is async per §3.2 #8, so the response path stays DB-free).
    'resolver_cache' => [
        'enabled' => env('RESOLVER_CACHE_ENABLED', true),
        'store' => env('RESOLVER_CACHE_STORE', 'resolver'),
        'ttl' => env('RESOLVER_CACHE_TTL', 3600),
        'warmup_limit' => env('RESOLVER_CACHE_WARMUP_LIMIT', 100),
        'metrics' => env('RESOLVER_CACHE_METRICS', true),
    ],

];
