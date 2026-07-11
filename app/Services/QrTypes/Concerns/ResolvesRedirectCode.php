<?php

declare(strict_types=1);

namespace App\Services\QrTypes\Concerns;

use App\Models\QrCode;

trait ResolvesRedirectCode
{
    /**
     * Resolve the configured redirect status code for a QR code.
     * Honors content.redirect_code and settings.redirect_code, defaulting to 302.
     * Only RFC-compliant redirect statuses are allowed.
     */
    protected function resolveRedirectCode(QrCode $qrCode): int
    {
        $code = $qrCode->content['redirect_code']
            ?? $qrCode->settings['redirect_code']
            ?? 302;

        $code = (int) $code;

        return in_array($code, [301, 302, 307, 308], true) ? $code : 302;
    }
}
