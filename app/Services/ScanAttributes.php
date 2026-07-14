<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Derives the request-side analytics attributes of a resolver scan
 * (Pflichtenheft §3.3.1, P3-T01 allowlist).
 *
 * Centralised here so the synchronous resolution path and the asynchronous
 * cache-hit scan job record identical attributes without duplicating the
 * user-agent / device / bot heuristics. This class produces only serialisable,
 * request-derived data (no DB, no GeoIP); enrichment and allowlist filtering
 * happen in {@see \App\Services\ScanRecorder}.
 */
class ScanAttributes
{
    /**
     * Capture the request-derived, serialisable slice of a scan (for the
     * async cache-hit job). Never includes the raw IP — only its HMAC hash —
     * and never touches the database.
     *
     * @return array{ip:?string,user_agent:string,referer:?string,accept_language:?string,utm_source:?string,utm_medium:?string,utm_campaign:?string,utm_term:?string,utm_content:?string}
     */
    public static function requestCapture(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent', ''),
            'referer' => $request->header('Referer'),
            'accept_language' => $request->header('Accept-Language'),
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
        ];
    }

    /**
     * Structured user-agent breakdown stored in the `user_agent_parsed` JSON
     * column (P3-T01 shape).
     *
     * @return array{device_type:?string,os_family:?string,browser_family:?string,is_bot:bool}
     */
    public static function parseUserAgent(string $ua): array
    {
        return [
            'device_type' => static::detectDeviceType($ua),
            'os_family' => static::detectOsFamily($ua),
            'browser_family' => static::detectBrowserFamily($ua),
            'is_bot' => static::detectBot($ua),
        ];
    }

    public static function detectBot(string $ua): bool
    {
        $botPatterns = [
            'bot', 'crawler', 'spider', 'slurp', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'telegrambot',
            'googlebot', 'bingbot', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function detectDeviceType(string $ua): ?string
    {
        if (empty($ua)) {
            return null;
        }

        if (preg_match('/(tablet|ipad|playbook|silk)/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|opera mini|iemobile)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function detectOsFamily(string $ua): ?string
    {
        if (empty($ua)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/windows|win32|win64/i', $ua) => 'Windows',
            (bool) preg_match('/macintosh|mac os x/i', $ua) => 'macOS',
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Other',
        };
    }

    public static function detectBrowserFamily(string $ua): ?string
    {
        if (empty($ua)) {
            return null;
        }

        return match (true) {
            (bool) preg_match('/edg/i', $ua) => 'Edge',
            (bool) preg_match('/chrome|crios/i', $ua) && ! preg_match('/edg/i', $ua) => 'Chrome',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/safari/i', $ua) && ! preg_match('/chrome|crios/i', $ua) => 'Safari',
            (bool) preg_match('/opera|opr/i', $ua) => 'Opera',
            default => 'Other',
        };
    }
}
