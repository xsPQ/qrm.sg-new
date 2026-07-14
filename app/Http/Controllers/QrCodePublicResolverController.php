<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\LogResolverScan;
use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Services\QrCodeResolver;
use App\Services\QrCodeRouteService;
use App\Services\ResolverCache;
use App\Services\ScanAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QrCodePublicResolverController extends Controller
{
    private const SECURITY_HEADERS = [
        'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        'X-Content-Type-Options' => 'nosniff',
    ];

    public function __construct(
        private QrCodeResolver $resolver,
        private QrCodeRouteService $routeService,
        private ResolverCache $cache,
    ) {}

    /**
     * Resolve a public QR code scan via its short code or alias.
     * Registered as the catch-all LAST web route: GET /{codeOrAlias}.
     *
     * Flow (Pflichtenheft §3.2, §3.2.3 P3-T05):
     *  1. Try the resolver cache. A hit serves the stored response from Redis
     *     with no DB roundtrip and dispatches async scan counting/analytics.
     *  2. On a miss, run the live resolution (route lookup, atomic consume,
     *     type handler, synchronous scan log) and — for cacheable codes —
     *     store the rendered response, TTL-bounded by expires_at.
     */
    public function resolve(string $codeOrAlias, Request $request): Response
    {
        $host = $request->getHost();

        // 1. Warm-cache fast path: serve the stored response without touching
        //    the DB and count the scan asynchronously (§3.2 #8).
        if ($payload = $this->cache->get($host, $codeOrAlias)) {
            $this->dispatchCacheHitScan($request, $payload);

            return $this->applySecurityHeaders($this->cache->toResponse($payload));
        }

        // 2. Cache miss: resolve live.
        /** @var QrCodeRoute|null $route */
        $route = $this->routeService->findByCodeOrAlias($codeOrAlias, $host);

        if (! $route) {
            return $this->errorResponse(404, 'not_found');
        }

        $qrCode = $route->qrCode()->withTrashed()->first();

        if (! $qrCode || $qrCode->trashed()) {
            return $this->errorResponse(404, 'not_found');
        }

        if (! $qrCode->isActive()) {
            return $this->errorResponse(
                $this->statusForInactive($qrCode),
                $this->reasonForInactive($qrCode),
                $qrCode,
            );
        }

        $result = $this->resolver->resolve($qrCode, $request);

        $response = $this->toSecuredResponse($result);

        // 3. Persist the rendered response for cacheable codes only. Non-
        //    cacheable codes (burn / max_scans / password / expired) are never
        //    stored, so their live atomic guarantees are always in effect.
        $this->storeIfCacheable($host, $codeOrAlias, $qrCode, $response);

        return $response;
    }

    /**
     * Dispatch the asynchronous scan counter + analytics for a cache hit. Runs
     * inline under the `sync` queue driver (test suite) so counting stays
     * observable there.
     *
     * @param  array<string,mixed>  $payload
     */
    private function dispatchCacheHitScan(Request $request, array $payload): void
    {
        $qrCodeId = (int) ($payload['qr_code_id'] ?? 0);

        if ($qrCodeId <= 0) {
            return;
        }

        LogResolverScan::dispatch(
            $qrCodeId,
            ScanAttributes::requestCapture($request),
            [
                'response_type' => ! empty($payload['location']) ? 'redirect' : 'view',
                'http_status' => (int) $payload['status'],
                'response_time_ms' => 0,
            ],
        );
    }

    private function storeIfCacheable(string $host, string $slug, QrCode $qrCode, Response $response): void
    {
        if (! $this->cache->isCacheable($qrCode)) {
            return;
        }

        $payload = $this->cache->captureResponse($response);

        if ($payload === null) {
            return;
        }

        $this->cache->put($host, $slug, (int) $qrCode->id, $payload, $qrCode->expires_at);
    }

    /**
     * Map an inactive QR code to its public HTTP status code.
     * disabled -> 423 Locked; expired/burned/exhausted -> 410 Gone.
     */
    private function statusForInactive(QrCode $qrCode): int
    {
        if ($qrCode->status === 'disabled') {
            return 423;
        }

        return 410;
    }

    /**
     * Determine the human-facing reason for an unavailable QR code.
     */
    private function reasonForInactive(QrCode $qrCode): string
    {
        if ($qrCode->isExpired()) {
            return 'expired';
        }

        if ($qrCode->status === 'burned' || $qrCode->isBurned()) {
            return 'burned';
        }

        if ($qrCode->hasReachedMaxScans()) {
            return 'max_scans';
        }

        return 'inactive';
    }

    private function errorResponse(int $status, string $reason, ?QrCode $qrCode = null): Response
    {
        return response()
            ->view('qr-types.error', [
                'qrCode' => $qrCode,
                'reason' => $reason,
            ], $status)
            ->withHeaders(self::SECURITY_HEADERS);
    }

    /**
     * Normalize the resolver's mixed result into a secured Symfony Response.
     * Security headers (CSP + nosniff) are applied to every public response.
     */
    private function toSecuredResponse(mixed $result): Response
    {
        if ($result instanceof View) {
            // Rebuild as a normal view response so feature tests can assert the
            // original view name while still rendering HTML with headers.
            return response()
                ->view($result->name(), $result->getData(), 200)
                ->withHeaders(self::SECURITY_HEADERS);
        }

        if ($result instanceof RedirectResponse) {
            return $result->withHeaders(self::SECURITY_HEADERS);
        }

        if ($result instanceof Response) {
            return $result->withHeaders(self::SECURITY_HEADERS);
        }

        return response($result, 200)->withHeaders(self::SECURITY_HEADERS);
    }

    private function applySecurityHeaders(Response $response): Response
    {
        return $response->withHeaders(self::SECURITY_HEADERS);
    }
}
