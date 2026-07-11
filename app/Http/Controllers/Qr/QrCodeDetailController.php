<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Services\QrPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * QR-Code detail page (Pflichtenheft §3.6.1 / §3.7, P2-T02).
 *
 * Authenticated single-code view reachable from the dashboard list (P2-T01).
 * Access is restricted to the code's owner and admins through the QrCodePolicy
 * (P1-T15). Renders the full metadata set, a type-aware payload preview, the
 * rendered QR image and links to the SVG/PNG download endpoints (P1-T13).
 *
 * Visual customization (FEAT-04): the preview renders with the code's stored
 * style settings, gated by the code's entitlement snapshot.
 */
class QrCodeDetailController extends Controller
{
    public function __construct(private readonly QrPreviewService $previewService)
    {
    }

    public function show(Request $request, QrCode $qrCode)
    {
        // Owner/admin gate (P1-T15). Non-owners receive 403.
        $this->authorize('view', $qrCode);

        $qrCode->loadMissing('route');

        $route = $qrCode->route;
        $code = $route?->code ?? (string) $qrCode->id;

        // Build the exact short link the printed QR encodes (matches the
        // download controller), so the preview reflects the real scan target.
        $host = $route?->host
            ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
        $shortLink = "https://{$host}/{$code}";

        // Apply the code's stored visual style to the preview (FEAT-04).
        $style = $qrCode->settings['style'] ?? null;
        $snapshot = $qrCode->entitlementSnapshot();

        $previewDataUri = $this->previewService->dataUri(
            $shortLink,
            320,
            $style,
            $snapshot,
        );

        // last_scanned_at (Pflichtenheft §3.6.1 metadata): the most recent
        // scan timestamp for this code, read from the scans table.
        $lastScannedRaw = $qrCode->scans()->max('scanned_at');
        $lastScannedAt = $lastScannedRaw !== null ? Carbon::parse($lastScannedRaw) : null;

        return view('qr-codes.show', [
            'qrCode' => $qrCode,
            'route' => $route,
            'code' => $code,
            'shortLink' => $shortLink,
            'previewDataUri' => $previewDataUri,
            'downloadSvgUrl' => route('qr.download.svg', $code),
            'downloadPngUrl' => route('qr.download.png', $code),
            'lastScannedAt' => $lastScannedAt,
        ]);
    }
}
