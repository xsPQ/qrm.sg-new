<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Analytics\AllowlistGuard;
use App\Domain\Analytics\LocalGeoIpResolver;
use App\Models\Scan;

/**
 * Single source of truth for building and persisting a resolver scan row
 * (Pflichtenheft §3.3.1, P3-T01 allowlist).
 *
 * Both the synchronous resolution path (live miss) and the asynchronous
 * cache-hit job delegate here, so a scan recorded on a cache hit is byte-for-
 * byte consistent with one recorded on a miss. GeoIP enrichment and the
 * privacy allowlist filter are applied centrally; no caller may bypass them.
 */
class ScanRecorder
{
    public function __construct(
        private LocalGeoIpResolver $geoIpResolver,
        private AllowlistGuard $allowlistGuard,
    ) {}

    /**
     * Build the allowlist-sanitised scan row from serialisable request data
     * and the response meta. Does not persist.
     *
     * @param  array{ip:?string,user_agent:string,referer:?string,accept_language:?string,utm_source:?string,utm_medium:?string,utm_campaign:?string,utm_term:?string,utm_content:?string}  $request
     * @param  array{response_type:string,http_status:int,response_time_ms:int,qr_code_variant_id?:?int}  $response
     * @return array<string,mixed>
     */
    public function buildRow(int $qrCodeId, array $request, array $response): array
    {
        $ip = $request['ip'] ?? null;
        $ua = (string) ($request['user_agent'] ?? '');

        $geo = $ip !== null && $ip !== ''
            ? $this->geoIpResolver->resolve($ip)
            : ['country' => null, 'region' => null, 'city' => null, 'asn' => null];

        $rawData = [
            'qr_code_id' => $qrCodeId,
            'qr_code_variant_id' => $response['qr_code_variant_id'] ?? null,
            'ip_hash' => hash_hmac('sha256', $ip ?? '', (string) config('app.key')),
            'user_agent_raw' => $ua,
            'user_agent_parsed' => ScanAttributes::parseUserAgent($ua),
            'referer' => $this->truncate($request['referer'] ?? null, (int) config('analytics.max_referer_length', 500)),
            'accept_language' => $this->truncate($request['accept_language'] ?? null, (int) config('analytics.max_accept_language_length', 100)),
            'utm_source' => $request['utm_source'] ?? null,
            'utm_medium' => $request['utm_medium'] ?? null,
            'utm_campaign' => $request['utm_campaign'] ?? null,
            'utm_term' => $request['utm_term'] ?? null,
            'utm_content' => $request['utm_content'] ?? null,
            'geo_country' => $geo['country'] ?? null,
            'geo_region' => $geo['region'] ?? null,
            'geo_city' => $geo['city'] ?? null,
            'geo_asn' => $geo['asn'] ?? null,
            'is_bot' => ScanAttributes::detectBot($ua),
            'device_type' => ScanAttributes::detectDeviceType($ua),
            'os_family' => ScanAttributes::detectOsFamily($ua),
            'browser_family' => ScanAttributes::detectBrowserFamily($ua),
            'response_type' => $response['response_type'],
            'http_status' => $response['http_status'],
            'response_time_ms' => $response['response_time_ms'],
            'scanned_at' => now(),
        ];

        return $this->allowlistGuard->sanitize($rawData);
    }

    /**
     * Build and persist a single scan row.
     *
     * @param  array{ip:?string,user_agent:string,referer:?string,accept_language:?string,utm_source:?string,utm_medium:?string,utm_campaign:?string,utm_term:?string,utm_content:?string}  $request
     * @param  array{response_type:string,http_status:int,response_time_ms:int,qr_code_variant_id?:?int}  $response
     */
    public function record(int $qrCodeId, array $request, array $response): void
    {
        Scan::create($this->buildRow($qrCodeId, $request, $response));
    }

    private function truncate(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength);
    }
}
