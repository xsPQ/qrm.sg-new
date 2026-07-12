<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * FEAT-08: Rate-limit the public QR resolver per IP.
 *
 * Prevents abuse (scraping, DoS via rapid scans). Limits are config-driven
 * (qr.fair_use.resolver_scans_per_minute, default 100/min).
 * Returns HTTP 429 with Retry-After header when exceeded.
 */
class ResolverRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxPerMinute = (int) config('qr.fair_use.resolver_scans_per_minute', 100);

        $executed = RateLimiter::attempt(
            'resolver:' . $request->ip(),
            $maxPerMinute,
            fn () => true,
            60,
        );

        if (! $executed) {
            return response()->json([
                'error' => 'Too many requests. Please slow down.',
            ], 429, [
                'Retry-After' => '60',
            ]);
        }

        return $next($request);
    }
}
