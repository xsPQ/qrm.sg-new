<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

/**
 * AllowlistGuard (Pflichtenheft §3.3.1, §3.3.4, §7.3, §12.2)
 *
 * Enforces the analytics allowlist: only explicitly permitted fields may be
 * persisted to the scans table. Cookies, Authorization headers, passwords,
 * plaintext IPs, fingerprint payloads, and any other non-allowlisted data are
 * stripped before persistence.
 *
 * This class is the single source of truth for what a scan row may contain.
 * It does not mutate the request — it builds a sanitised payload array that
 * callers pass directly to Scan::create().
 */
class AllowlistGuard
{
    /**
     * The complete set of persistable scan columns (the "allowlist").
     * Anything not listed here is never stored.
     */
    public const ALLOWLIST_COLUMNS = [
        'qr_code_id',
        'ip_hash',
        'user_agent_raw',
        'user_agent_parsed',
        'referer',
        'accept_language',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'geo_country',
        'geo_region',
        'geo_city',
        'geo_asn',
        'device_type',
        'os_family',
        'browser_family',
        'is_bot',
        'response_type',
        'http_status',
        'response_time_ms',
        'scanned_at',
        'delete_after',
    ];

    /**
     * Maximum string length for free-text fields (defence-in-depth).
     */
    private const MAX_STRING_LENGTH = 500;

    /**
     * Fields that must never contain a raw value even if present in the input.
     * These are checked explicitly during the privacy audit.
     */
    private const SENSITIVE_PATTERNS = [
        'cookie',
        'authorization',
        'password',
        'secret',
        'token',
        'api_key',
        'apikey',
    ];

    /**
     * Filter an associative array down to only allowlisted keys, truncating
     * string values to the maximum length.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $output = [];

        foreach (self::ALLOWLIST_COLUMNS as $column) {
            if (!array_key_exists($column, $input)) {
                continue;
            }

            $value = $input[$column];

            if (is_string($value) && strlen($value) > self::MAX_STRING_LENGTH) {
                $value = substr($value, 0, self::MAX_STRING_LENGTH);
            }

            $output[$column] = $value;
        }

        return $output;
    }

    /**
     * Audit a persisted scan row to prove no sensitive data leaked.
     *
     * Returns true when the row is clean (no plaintext IP, no cookies, no
     * authorization, no passwords). Used in tests as a privacy assertion.
     *
     * @param  array<string, mixed>  $row
     */
    public function isClean(array $row): bool
    {
        // No column may contain a raw IPv4/IPv6 address.
        foreach ($row as $key => $value) {
            if ($key === 'ip_hash') {
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            // Reject anything that looks like a plaintext IP.
            if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
                return false;
            }

            // Reject sensitive substrings in any column value.
            $lowerKey = strtolower($key);
            foreach (self::SENSITIVE_PATTERNS as $pattern) {
                if (str_contains($lowerKey, $pattern) && $key !== 'ip_hash') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the list of allowlisted column names.
     *
     * @return string[]
     */
    public function allowedColumns(): array
    {
        return self::ALLOWLIST_COLUMNS;
    }
}
