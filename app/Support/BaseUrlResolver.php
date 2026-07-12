<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the effective base URL for QR-code links.
 *
 * Uses the actual request scheme + host (so visiting http://qrm.sg:8000/aB3x
 * shows "http://qrm.sg:8000/aB3x", not the fixed APP_URL). Falls back to
 * config('app.url') for CLI/queue contexts where no request exists.
 *
 * This is essential for White-Label/Custom-Domain support: when a Business
 * customer operates on qr.firma.de, all displayed links must reflect that
 * domain, not the APP_URL default.
 */
class BaseUrlResolver
{
    /**
     * Returns the base URL (scheme + host + port) for QR-code links.
     */
    public static function forRequest(?Request $request = null): string
    {
        $request ??= request();

        if ($request && $request->getHost()) {
            $scheme = $request->getScheme();
            $host = $request->getHost();
            $port = $request->getPort();

            // Omit default ports (80 for http, 443 for https)
            $isDefaultPort = ($scheme === 'http' && $port === 80)
                || ($scheme === 'https' && $port === 443)
                || $port === null;

            return $isDefaultPort
                ? "{$scheme}://{$host}"
                : "{$scheme}://{$host}:{$port}";
        }

        // Fallback for CLI/queue/cron contexts
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Returns the host only (without scheme/port).
     */
    public static function host(?Request $request = null): string
    {
        $request ??= request();

        if ($request && $request->getHost()) {
            return $request->getHost();
        }

        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }

    /**
     * Builds the full scan URL for a QR-code route code/alias.
     */
    public static function scanUrl(string $code, ?Request $request = null): string
    {
        return self::forRequest($request) . '/' . $code;
    }
}
