<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use App\Services\QrTypes\Concerns\ResolvesRedirectCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UrlHandler implements QrTypeHandler
{
    use ResolvesRedirectCode;

    /**
     * Redirect the scanner to the URL stored in the QR code content.
     */
    public function handle(QrCode $qrCode, Request $request): RedirectResponse
    {
        $url = $qrCode->content['url'] ?? '';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return redirect()->away($url, $this->resolveRedirectCode($qrCode));
    }
}
