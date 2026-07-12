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
    'resolver_cache' => [
        'enabled' => env('RESOLVER_CACHE_ENABLED', true),
        'store' => env('RESOLVER_CACHE_STORE', 'resolver'),
        'ttl' => env('RESOLVER_CACHE_TTL', 3600),
        'warmup_limit' => env('RESOLVER_CACHE_WARMUP_LIMIT', 100),
        'metrics' => env('RESOLVER_CACHE_METRICS', true),
    ],

    // Fair-Use Policy (Pflichtenheft §12.9 FEAT-08).
    // Limits prevent abuse on paid plans. Grandfathered codes keep their
    // original rights; these limits apply to account-level resource creation.
    'fair_use' => [
        'free' => [
            'max_active_qr_codes' => env('FAIR_USE_FREE_MAX_QR', 10),
            'max_qr_per_hour' => env('FAIR_USE_FREE_QR_PER_HOUR', 5),
        ],
        'pro' => [
            'max_active_qr_codes' => env('FAIR_USE_PRO_MAX_QR', 500),
            'max_scans_per_day' => env('FAIR_USE_PRO_SCANS_DAY', 50000),
            'max_qr_per_hour' => env('FAIR_USE_PRO_QR_PER_HOUR', 20),
            'max_ab_variants' => env('FAIR_USE_PRO_AB_VARIANTS', 5),
            'api_rate_per_minute' => env('FAIR_USE_PRO_API_MIN', 60),
        ],
        'business' => [
            'max_active_qr_codes' => env('FAIR_USE_BIZ_MAX_QR', 5000),
            'max_scans_per_day' => env('FAIR_USE_BIZ_SCANS_DAY', 500000),
            'max_qr_per_hour' => env('FAIR_USE_BIZ_QR_PER_HOUR', 50),
            'max_ab_variants' => env('FAIR_USE_BIZ_AB_VARIANTS', 20),
            'api_rate_per_minute' => env('FAIR_USE_BIZ_API_MIN', 300),
        ],
        // Resolver rate-limit: per IP-hash, applies to all tiers.
        'resolver_scans_per_minute' => env('FAIR_USE_RESOLVER_PER_MIN', 100),

        // Suspicious activity detection threshold.
        'suspicious_codes_per_day' => env('FAIR_USE_SUSPICIOUS_CODES_DAY', 500),
    ],

];
