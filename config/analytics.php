<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | GeoIP Local Database (Pflichtenheft §3.3.4)
    |--------------------------------------------------------------------------
    |
    | Path to the local MaxMind GeoLite2 database files. These are read from
    | disk — no external HTTP call is ever made during a scan. When the files
    | are absent, geo fields gracefully resolve to null.
    |
    */

    'geoip' => [
        'city_db' => env('GEOIP_CITY_DB', storage_path('app/geoip/GeoLite2-City.mmdb')),
        'asn_db' => env('GEOIP_ASN_DB', storage_path('app/geoip/GeoLite2-ASN.mmdb')),
        'locales' => ['en', 'de'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Header Limits
    |--------------------------------------------------------------------------
    |
    | Maximum stored length for free-text header fields. Over-long values are
    | truncated before persistence as a defence-in-depth measure.
    |
    */

    'max_referer_length' => (int) env('ANALYTICS_MAX_REFERER_LENGTH', 500),
    'max_accept_language_length' => (int) env('ANALYTICS_MAX_ACCEPT_LANGUAGE_LENGTH', 100),

    /*
    |--------------------------------------------------------------------------
    | Raw-Scan Retention (Pflichtenheft §3.3.4, P3-T03)
    |--------------------------------------------------------------------------
    |
    | Raw scan rows are deleted on a schedule for data-protection compliance.
    | Only raw scans are purged; aggregated statistics (scan_stats_hourly /
    | scan_stats_daily) persist as long as the QR code and account exist.
    |
    |  - Free entitlement: a raw scan is deleted `free_days` days after the QR
    |    CODE was created (all scans of a Free code share one deadline).
    |  - Pro/Business entitlement: a raw scan is deleted `paid_months` months
    |    after the scan was recorded (scanned_at).
    |
    | The daily purge deletes in `purge_chunk_size`-sized batches to avoid long
    | lock holds on large tables.
    |
    */

    'retention' => [
        'free_days' => (int) env('QR_RETENTION_FREE_DAYS', 60),
        'paid_months' => (int) env('QR_RETENTION_PAID_MONTHS', 24),
        'purge_chunk_size' => (int) env('QR_RETENTION_PURGE_CHUNK_SIZE', 1000),
    ],

];
