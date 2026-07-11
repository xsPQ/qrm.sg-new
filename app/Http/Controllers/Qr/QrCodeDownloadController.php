<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Domain\Entitlement\EntitlementGate;
use App\Http\Controllers\Controller;
use App\Models\QrCodeRoute;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodeDownloadController extends Controller
{
    public function __construct(
        private EntitlementGate $gate,
    ) {}

    public function downloadSvg(Request $request, string $code): Response|StreamedResponse
    {
        return $this->serveQrCode($request, $code, 'svg');
    }

    public function downloadPng(Request $request, string $code): Response|StreamedResponse
    {
        return $this->serveQrCode($request, $code, 'png');
    }

    private function serveQrCode(Request $request, string $code, string $format): Response|StreamedResponse
    {
        $qrCodeRoute = QrCodeRoute::with('qrCode')
            ->where(function ($query) use ($code) {
                $query->where('code', $code)
                    ->orWhere('alias', $code);
            })
            ->first();

        if (!$qrCodeRoute || !$qrCodeRoute->qrCode) {
            abort(404);
        }

        $qrCode = $qrCodeRoute->qrCode;

        if ($qrCode->trashed()) {
            abort(404);
        }

        if ($qrCode->status === 'disabled') {
            abort(423);
        }

        $host = $qrCodeRoute->host ?: parse_url(config('app.url'), PHP_URL_HOST);
        $data = "https://{$host}/{$qrCodeRoute->code}";

        // Download profile (P2-T07, §2.1/§2.2): the per-code entitlement
        // snapshot drives both the branding and the maximum resolution. Free
        // (`standard`) downloads are capped; Pro/Business (`high_resolution`)
        // may use the full size. Requested size is clamped to the plan ceiling.
        $snapshot = $qrCode->entitlementSnapshot();
        $maxSize = $this->gate->maxDownloadSize($snapshot);

        $size = (int) $request->query('size', 300);
        $size = max(100, min($size, $maxSize));

        $needsBranding = $this->gate->requiresBranding($snapshot);

        if ($format === 'svg') {
            $builder = Builder::create()
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->size($size)
                ->margin(10)
                ->writer(new SvgWriter());

            $result = $builder->build();
            $content = $result->getString();
            $contentType = 'image/svg+xml';

            if ($needsBranding) {
                $content = $this->addSvgBranding($content, $size);
            }
        } else {
            $builder = Builder::create()
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->size($size)
                ->margin(10)
                ->writer(new PngWriter());

            if ($needsBranding) {
                $builder = $builder->labelText('qrm.sg')
                    ->labelFont(new NotoSans(12));
            }

            $result = $builder->build();
            $content = $result->getString();
            $contentType = 'image/png';
        }

        $filename = "qr-{$qrCodeRoute->code}.{$format}";

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function addSvgBranding(string $svg, int $size): string
    {
        $labelHeight = 30;
        $margin = 10;
        $outerSize = $size + $margin * 2;
        $totalHeight = $outerSize + $labelHeight;

        $svg = preg_replace(
            '/width="\d+px"/',
            'width="' . $outerSize . 'px"',
            $svg,
            1
        );
        $svg = preg_replace(
            '/height="\d+px"/',
            'height="' . $totalHeight . 'px"',
            $svg,
            1
        );
        $svg = preg_replace(
            '/viewBox="0 0 \d+ \d+"/',
            'viewBox="0 0 ' . $outerSize . ' ' . $totalHeight . '"',
            $svg,
            1
        );

        $textX = $outerSize / 2;
        $textY = $outerSize + ($labelHeight / 2) + 4;

        $textElement = sprintf(
            '<text x="%d" y="%d" font-family="sans-serif" font-size="12" fill="#000000" text-anchor="middle" dominant-baseline="middle">qrm.sg</text>',
            (int) $textX,
            (int) $textY
        );

        $svg = str_replace('</svg>', $textElement . '</svg>', $svg);

        return $svg;
    }
}
