<?php

namespace App\Services;

use App\Domain\Analytics\AllowlistGuard;
use App\Domain\Analytics\LocalGeoIpResolver;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Services\QrTypes\EventHandler;
use App\Services\QrTypes\CryptoHandler;
use App\Services\QrTypes\MessageHandler;
use App\Services\QrTypes\QrTypeHandler;
use App\Services\QrTypes\RedirectHandler;
use App\Services\QrTypes\SocialHandler;
use App\Services\QrTypes\UrlHandler;
use App\Services\QrTypes\VcardHandler;
use App\Services\QrTypes\WifiHandler;
use App\Services\VariantSelector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrCodeResolver
{
    private array $handlers = [];

    private ScanRecorder $scanRecorder;

    private ResolverCache $cache;

    private VariantSelector $variantSelector;

    /** Tracks the selected variant id for the current scan (FEAT-06). */
    private ?int $currentVariantId = null;

    public function __construct(
        ?LocalGeoIpResolver $geoIpResolver = null,
        AllowlistGuard $allowlistGuard = new AllowlistGuard(),
        ?ScanRecorder $scanRecorder = null,
        ?ResolverCache $cache = null,
        ?VariantSelector $variantSelector = null,
    ) {
        $this->scanRecorder = $scanRecorder ?? new ScanRecorder(
            $geoIpResolver ?? new LocalGeoIpResolver(
                cityDbPath: config('analytics.geoip.city_db'),
                asnDbPath: config('analytics.geoip.asn_db'),
                locales: config('analytics.geoip.locales', ['en', 'de']),
            ),
            $allowlistGuard,
        );

        $this->cache = $cache ?? new ResolverCache();
        $this->variantSelector = $variantSelector ?? new VariantSelector();

        $this->handlers = [
            QrCodeType::Url->value => new UrlHandler,
            QrCodeType::Message->value => new MessageHandler,
            QrCodeType::Redirect->value => new RedirectHandler,
            QrCodeType::Social->value => new SocialHandler,
            QrCodeType::Wifi->value => new WifiHandler,
            QrCodeType::Crypto->value => new CryptoHandler,
            QrCodeType::Event->value => new EventHandler,
            QrCodeType::Vcard->value => new VcardHandler,
            'contact' => new VcardHandler, // alias: Pflichtenheft uses "Contact", enum uses "vcard"
        ];
    }

    /**
     * Register a custom handler for a QR code type.
     */
    public function extend(string $type, QrTypeHandler $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    /**
     * Resolve a QR code scan: validate, log, delegate to type handler.
     * Uses atomic conditional UPDATE for burn/max_scans concurrency safety.
     */
    public function resolve(QrCode $qrCode, Request $request): mixed
    {
        if (!$qrCode->isActive()) {
            return $this->handleInactive($qrCode);
        }

        if ($qrCode->password_hash && !$this->isPasswordVerified($qrCode, $request)) {
            return $this->handlePasswordRequired($qrCode);
        }

        // Atomic conditional consume: a single UPDATE that increments the scan
        // count and burns the code (when applicable) only while status = active.
        // Under concurrent requests the conditional WHERE guarantees that burn
        // codes deliver exactly once and max_scans codes deliver at most N times;
        // every losing request matches 0 rows and receives the gone response.
        if (!$this->consumeScanAtomic($qrCode)) {
            return $this->handleInactive($qrCode);
        }

        // Defensive invalidation on burn / max_scans completion (P3-T05). These
        // codes are never cached, so this is a safe no-op in practice, but it
        // documents the consistency contract and guards against future changes
        // to cacheability rules.
        if ($qrCode->burn || $qrCode->max_scans !== null) {
            $this->cache->forgetFor($qrCode);
        }

        $startTime = microtime(true);

        // FEAT-06: A/B Testing — if this QR code has variants configured,
        // select one by strategy, increment its scan count, and redirect
        // to the variant's URL. The variant_id is stamped on the scan row
        // for per-variant analytics. Only applies to url/redirect types.
        $this->currentVariantId = null;

        if ($this->supportsVariants($qrCode) && $qrCode->hasVariants()) {
            $response = $this->resolveWithVariant($qrCode, $request);
        } else {
            $handler = $this->getHandler($qrCode->type);
            $response = $handler->handle($qrCode, $request);
        }

        $this->logScan($qrCode, $request, $startTime, $response);

        return $response;
    }

    /**
     * Verify scan password via the signed grant cookie only.
     *
     * DEV-613 (F1/F2): the GET resolver never accepts a query-string password.
     * The unthrottled GET path was a brute-force bypass and leaked the password
     * in URLs/logs. Password verification happens solely through the dedicated
     * POST /r/{code}/password endpoint, which sets this signed cookie.
     */
    public function isPasswordVerified(QrCode $qrCode, Request $request): bool
    {
        $cookieName = 'qr_password_' . $qrCode->id;

        if ($request->cookies->has($cookieName)) {
            $token = $request->cookies->get($cookieName);
            $expected = hash_hmac('sha256', $qrCode->id . $qrCode->password_hash, config('app.key'));
            if (hash_equals($expected, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render a cacheable code's type response WITHOUT consuming a scan or
     * logging analytics. Used by the resolver-cache warmup command so it can
     * pre-populate the cache for the hottest codes after a deploy without
     * inflating scan counts. Only call this for codes that pass
     * {@see ResolverCache::isCacheable()}.
     */
    public function renderForCache(QrCode $qrCode, Request $request): mixed
    {
        return $this->getHandler($qrCode->type)->handle($qrCode, $request);
    }

    public function getHandler(string $type): QrTypeHandler
    {
        if (isset($this->handlers[$type])) {
            return $this->handlers[$type];
        }

        throw new \InvalidArgumentException("No handler registered for QR code type: {$type}");
    }

    public function hasHandler(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    private function handleInactive(QrCode $qrCode): mixed
    {
        $reason = 'inactive';

        if ($qrCode->isExpired()) {
            $reason = 'expired';
        } elseif ($qrCode->isBurned()) {
            $reason = 'burned';
        } elseif ($qrCode->hasReachedMaxScans()) {
            $reason = 'max_scans';
        }

        // Return a proper gone/locked Response (not a bare View) so that the
        // public controller preserves the HTTP status. This is the race-loser
        // path: a request whose in-memory model was still active but lost the
        // atomic consume UPDATE must receive 410 Gone (423 for disabled),
        // matching the resolver/acceptance contract for burn & max_scans.
        $status = $qrCode->status === 'disabled' ? 423 : 410;

        return response()->view('qr-types.error', [
            'qrCode' => $qrCode,
            'reason' => $reason,
        ], $status);
    }

    private function handlePasswordRequired(QrCode $qrCode): mixed
    {
        return view('qr-types.password', [
            'qrCode' => $qrCode,
        ]);
    }

    /**
     * Atomically consume a scan for the given QR code.
     *
     * The conditional WHERE (status = active) is the concurrency guard: burn
     * codes are marked burned on the first winning request, so every later
     * request matches 0 rows; max_scans codes burn once scan_count reaches the
     * limit. Returns true when this request consumed the scan.
     */
    private function consumeScanAtomic(QrCode $qrCode): bool
    {
        $affected = DB::update(
            'UPDATE qr_codes
                SET scan_count = scan_count + 1,
                    status = CASE
                        WHEN burn = ? THEN ?
                        WHEN max_scans IS NOT NULL AND (scan_count + 1) >= max_scans THEN ?
                        ELSE status
                    END,
                    updated_at = ?
                WHERE id = ? AND status = ?',
            [true, 'burned', 'burned', now(), $qrCode->id, 'active'],
        );

        return $affected > 0;
    }

    /**
     * FEAT-06: Whether the QR code type supports A/B test variants.
     * Only 'url' and 'redirect' types can have multiple destination URLs.
     */
    private function supportsVariants(QrCode $qrCode): bool
    {
        return in_array($qrCode->type, ['url', 'redirect'], true);
    }

    /**
     * FEAT-06: Select a variant by strategy, increment its scan count,
     * and redirect to the variant's destination URL.
     */
    private function resolveWithVariant(QrCode $qrCode, Request $request): RedirectResponse
    {
        $variant = $this->variantSelector->selectAndIncrement($qrCode, $request);

        // Fallback: if variant selection returned null (race condition edge
        // case), delegate to the normal handler.
        if ($variant === null) {
            return $this->getHandler($qrCode->type)->handle($qrCode, $request);
        }

        $this->currentVariantId = $variant->id;

        $url = $variant->url;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $status = $qrCode->content['redirect_code']
            ?? $qrCode->settings['redirect_code']
            ?? 302;

        $status = in_array((int) $status, [301, 302, 307, 308], true) ? (int) $status : 302;

        return redirect()->away($url, $status);
    }

    private function logScan(QrCode $qrCode, Request $request, float $startTime, mixed $response): void
    {
        $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        $httpStatus = 200;
        $responseType = 'view';

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $httpStatus = $response->getStatusCode();
            $responseType = 'redirect';
        }

        // Delegate to the shared recorder so cache-hit and cache-miss scans are
        // recorded identically (GeoIP + allowlist applied centrally).
        $this->scanRecorder->record(
            (int) $qrCode->id,
            ScanAttributes::requestCapture($request),
            [
                'response_type' => $responseType,
                'http_status' => $httpStatus,
                'response_time_ms' => $responseTimeMs,
                'qr_code_variant_id' => $this->currentVariantId,
            ],
        );
    }
}
