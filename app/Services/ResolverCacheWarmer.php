<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Pre-populates the resolver cache for the hottest active, unlimited codes
 * (Pflichtenheft §3.2.3, P3-T05 — "Cache-Warmup nach Deploy").
 *
 * Warmup renders each eligible code's response through the same type handlers
 * as a live resolve, but WITHOUT consuming a scan or logging analytics, then
 * stores the captured response under every public slug (code and alias) for
 * the app host. This removes the cold-start penalty for the most-scanned codes
 * right after a deploy or an explicit cache flush.
 */
class ResolverCacheWarmer
{
    private const SECURITY_HEADERS = [
        'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        'X-Content-Type-Options' => 'nosniff',
    ];

    public function __construct(
        private QrCodeResolver $resolver,
        private ResolverCache $cache,
    ) {}

    /**
     * Warm the cache for up to {limit} active, unlimited, non-password codes,
     * ranked by scan volume. Returns a summary of what was warmed.
     *
     * @return array{warmed:int,skipped:int,slugs:int}
     */
    public function warm(int $limit = 100): array
    {
        if (! $this->cache->enabled()) {
            return ['warmed' => 0, 'skipped' => 0, 'slugs' => 0];
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $limit = max(1, $limit);

        $codes = QrCode::query()
            ->where('status', 'active')
            ->where('burn', false)
            ->whereNull('max_scans')
            ->whereNull('password_hash')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('scan_count')
            ->with('route')
            ->limit($limit)
            ->get();

        $warmed = 0;
        $skipped = 0;
        $slugs = 0;

        foreach ($codes as $qrCode) {
            /** @var QrCodeRoute|null $route */
            $route = $qrCode->route;

            // Only warm routes that live on the app host. Custom-domain routes
            // (P4, out of scope) belong to a different host namespace.
            if (! $route || $route->host !== $host || ! $this->cache->isCacheable($qrCode)) {
                $skipped++;

                continue;
            }

            try {
                $response = $this->render($qrCode);
            } catch (Throwable $e) {
                Log::warning(sprintf('Resolver warmup failed for qr_code=%d: %s', $qrCode->id, $e->getMessage()));
                $skipped++;

                continue;
            }

            $payload = $this->cache->captureResponse($response);

            if ($payload === null) {
                $skipped++;

                continue;
            }

            // Warm every public slug for this code on the app host.
            foreach ($this->slugsFor($route) as $slug) {
                $this->cache->put($host, $slug, (int) $qrCode->id, $payload, $qrCode->expires_at);
                $slugs++;
            }

            $warmed++;
        }

        return ['warmed' => $warmed, 'skipped' => $skipped, 'slugs' => $slugs];
    }

    /**
     * Render a cacheable code into a fully-secured Response, mirroring the
     * public controller's wrapping (security headers + content-type).
     *
     * The return type is the Symfony base Response: type handlers may return
     * either an Illuminate Response (view/HTML) or a RedirectResponse (url /
     * redirect), and RedirectResponse does NOT extend Illuminate\Http\Response
     * (both extend the Symfony base). Declaring the narrower type previously
     * raised a TypeError on every redirect code, which the per-code try/catch
     * in warm() swallowed — silently warming nothing.
     */
    private function render(QrCode $qrCode): SymfonyResponse
    {
        $request = Request::create((string) config('app.url'), 'GET');
        $result = $this->resolver->renderForCache($qrCode, $request);

        if ($result instanceof View) {
            return response($result, 200, ['Content-Type' => 'text/html'])
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

    /**
     * Public slugs that resolve to this route (system code + custom alias).
     *
     * @return list<string>
     */
    private function slugsFor(QrCodeRoute $route): array
    {
        $slugs = [$route->code];

        if ($route->alias !== null) {
            $slugs[] = $route->alias;
        }

        return $slugs;
    }
}
