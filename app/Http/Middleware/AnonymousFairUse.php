<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AnonymousFairUse
{
    private const DAILY_LIMIT = 10;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        if ($response = $this->check($request)) {
            return $response;
        }

        return $next($request);
    }

    public function check(Request $request): ?Response
    {
        foreach ($this->keysForRequest($request) as $key) {
            if ($this->count($key) >= self::DAILY_LIMIT) {
                return response()->json([
                    'message' => 'Daily limit reached (10 QR codes per day). Sign up for unlimited codes.',
                ], 429);
            }
        }

        foreach ($this->keysForRequest($request) as $key) {
            $this->increment($key);
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function keysForRequest(Request $request): array
    {
        $keys = ['anon_fairuse:' . ($request->ip() ?: 'unknown')];

        $fingerprint = $request->header('X-Browser-Fingerprint') ?: $request->cookie('bf');
        if (is_string($fingerprint) && trim($fingerprint) !== '') {
            $keys[] = 'anon_fairuse:' . $this->normalizeFingerprint($fingerprint);
        }

        return $keys;
    }

    private function normalizeFingerprint(string $fingerprint): string
    {
        $fingerprint = trim($fingerprint);
        $fingerprint = preg_replace('/\s+/', '_', $fingerprint) ?? $fingerprint;
        $fingerprint = preg_replace('/[^A-Za-z0-9._:-]/', '_', $fingerprint) ?? $fingerprint;

        return $fingerprint !== '' ? $fingerprint : 'unknown';
    }

    private function count(string $key): int
    {
        return (int) Cache::get($key, 0);
    }

    private function increment(string $key): void
    {
        if (! Cache::has($key)) {
            Cache::put($key, 0, now()->addDay());
        }

        Cache::increment($key);
    }
}
