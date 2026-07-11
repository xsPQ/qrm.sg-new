<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use App\Services\QrTypes\Concerns\ResolvesRedirectCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectHandler implements QrTypeHandler
{
    use ResolvesRedirectCode;

    /**
     * Evaluate redirect rules and redirect the scanner to the matching destination.
     *
     * Rules are evaluated in order. The first matching rule wins.
     * If no rule matches, the default URL is used.
     *
     * Supported rule conditions:
     *  - device: mobile, desktop, tablet
     *  - os: ios, android, windows, macos, linux
     *  - country: ISO country code
     *  - language: Accept-Language prefix match
     *  - utm_source: matches referer/source
     */
    public function handle(QrCode $qrCode, Request $request): RedirectResponse
    {
        $rules = $qrCode->content['rules'] ?? [];
        $defaultUrl = $qrCode->content['default_url'] ?? $qrCode->content['url'] ?? '/';
        $status = $this->resolveRedirectCode($qrCode);

        $deviceType = $this->detectDevice($request);
        $osFamily = $this->detectOs($request);
        $country = $qrCode->scans()->latest('scanned_at')->first()?->geo_country;

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $deviceType, $osFamily, $country, $request)) {
                return redirect()->away($rule['url'], $status);
            }
        }

        if (!filter_var($defaultUrl, FILTER_VALIDATE_URL)) {
            $defaultUrl = 'https://' . ltrim($defaultUrl, '/');
        }

        return redirect()->away($defaultUrl, $status);
    }

    private function matchesRule(array $rule, string $device, string $os, ?string $country, Request $request): bool
    {
        if (!empty($rule['device']) && !in_array($device, (array) $rule['device'])) {
            return false;
        }

        if (!empty($rule['os']) && !in_array($os, (array) $rule['os'])) {
            return false;
        }

        if (!empty($rule['country'])) {
            $allowed = array_map('strtoupper', (array) $rule['country']);
            if (!$country || !in_array(strtoupper($country), $allowed)) {
                return false;
            }
        }

        if (!empty($rule['language'])) {
            $acceptLang = $request->header('Accept-Language', '');
            $allowed = (array) $rule['language'];
            $match = false;
            foreach ($allowed as $lang) {
                if (str_starts_with($acceptLang, $lang)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                return false;
            }
        }

        return true;
    }

    private function detectDevice(Request $request): string
    {
        $ua = $request->header('User-Agent', '');

        if (preg_match('/(tablet|ipad|playbook|silk)/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|opera mini|iemobile)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function detectOs(Request $request): string
    {
        $ua = $request->header('User-Agent', '');

        return match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'ios',
            (bool) preg_match('/android/i', $ua) => 'android',
            (bool) preg_match('/windows|win32|win64/i', $ua) => 'windows',
            (bool) preg_match('/macintosh|mac os x/i', $ua) => 'macos',
            (bool) preg_match('/linux/i', $ua) => 'linux',
            default => 'unknown',
        };
    }
}
