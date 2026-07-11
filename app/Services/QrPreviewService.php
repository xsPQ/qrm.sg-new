<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entitlement\EntitlementSnapshot;
use App\Domain\QrTypes\SocialQr;
use App\Enums\QrCodeType;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Builds a live QR-code preview for the Creator-UI (P2-T04).
 *
 * The preview is rendered in the form before the code exists. When the user
 * supplies a custom alias, the preview encodes the real public short-link
 * ({APP_URL}/{alias}) — exactly what the printed QR will resolve to via the
 * public resolver (P1-T11). When no alias is set yet, it encodes a
 * representative content payload (the URL, the WiFi: string, a vCard, …) so
 * the user still gets a meaningful, type-aware preview while typing.
 *
 * Visual customization (FEAT-04): foreground/background colors, dot (block)
 * roundness, error-correction level, gradient and embedded logo are applied
 * through {@see QrStyleService}, gated by the caller's entitlement snapshot.
 */
class QrPreviewService
{
    /**
     * Resolve the public short-link URL for a (future) code.
     * Uses the alias when provided, otherwise a placeholder token.
     */
    public function resolveUrl(?string $alias = null): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return $alias
            ? $base . '/' . $alias
            : $base . '/<auto>';
    }

    /**
     * Build the most useful preview payload for the given type + content.
     */
    public function payloadFor(string $type, array $content, ?string $alias = null): string
    {
        if ($alias !== null && $alias !== '') {
            return $this->resolveUrl($alias);
        }

        $enum = QrCodeType::tryFrom($type);

        return match ($enum) {
            QrCodeType::Url => $this->string($content['url'] ?? ''),
            QrCodeType::Redirect => $this->string($content['target_url'] ?? ''),
            QrCodeType::Message => $this->string($content['message'] ?? ''),
            QrCodeType::Social => $this->socialPayload($content),
            QrCodeType::Wifi => $this->wifiPayload($content),
            QrCodeType::Crypto => $this->cryptoPayload($content),
            QrCodeType::Event => $this->eventPayload($content),
            QrCodeType::Vcard => $this->vcardPayload($content),
            default => $this->resolveUrl($alias),
        };
    }

    /**
     * Render a payload as a PNG data URI for inline <img> display.
     * Never throws: on any rendering error it returns null and the UI shows a
     * neutral placeholder instead of breaking the whole form.
     *
     * Visual customization (FEAT-04): when $style and $snapshot are provided,
     * the rendered preview reflects the user's color / dot-style / gradient /
     * logo selections, gated by their entitlement tier.
     *
     * @param  array<string,mixed>|null  $style
     */
    public function dataUri(
        string $payload,
        int $size = 240,
        ?array $style = null,
        ?EntitlementSnapshot $snapshot = null,
    ): ?string {
        if ($payload === '') {
            return null;
        }

        try {
            $builder = Builder::create()
                ->writer(new PngWriter())
                ->data($payload)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->size($size)
                ->margin(8);

            // Apply visual customization (FEAT-04) when style data is present.
            if ($style !== null && $snapshot !== null) {
                $styleService = app(QrStyleService::class);
                $resolved = $styleService->resolveStyle($style, $snapshot);
                $styleService->applyStyle($builder, $resolved);

                // Re-apply size/margin since applyStyle may override margin.
                $builder->size($size);
            }

            $result = $builder->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function socialPayload(array $content): string
    {
        $platform = $this->string($content['platform'] ?? '');
        $username = $this->string($content['username'] ?? '');

        if ($platform !== '' && $username !== '') {
            return SocialQr::computeProfileUrl($platform, $username) ?: $username;
        }

        return $username;
    }

    private function wifiPayload(array $content): string
    {
        $ssid = $this->string($content['ssid'] ?? '');
        $encryption = $this->string($content['encryption'] ?? 'WPA');
        $password = $this->string($content['password'] ?? '');
        $hidden = !empty($content['hidden']);

        if ($ssid === '') {
            return '';
        }

        // WIFI: URI scheme (https://en.wikipedia.org/wiki/Qr_code#WiFi).
        $type = in_array($encryption, ['WPA', 'WPA2', 'WEP'], true) ? $encryption : 'nopass';

        return sprintf(
            'WIFI:T:%s;S:%s;P:%s;H:%s;;',
            $type,
            $this->escapeWifi($ssid),
            $this->escapeWifi($password),
            $hidden ? 'true' : 'false',
        );
    }

    private function cryptoPayload(array $content): string
    {
        $currency = strtoupper($this->string($content['currency'] ?? ''));
        $address = $this->string($content['address'] ?? '');

        if ($currency === '' || $address === '') {
            return $address;
        }

        $scheme = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'USDT' => 'ethereum',
            'USDC' => 'ethereum',
        ][$currency] ?? strtolower($currency);

        $params = [];
        if (!empty($content['amount'])) {
            $params['amount'] = $content['amount'];
        }
        if (!empty($content['label'])) {
            $params['label'] = $content['label'];
        }

        return $params !== []
            ? $scheme . ':' . $address . '?' . http_build_query($params)
            : $scheme . ':' . $address;
    }

    private function eventPayload(array $content): string
    {
        $title = $this->string($content['title'] ?? '');
        if ($title === '') {
            return '';
        }

        $start = $this->string($content['start'] ?? '');
        $end = $this->string($content['end'] ?? '');
        $location = $this->string($content['location'] ?? '');
        $description = $this->string($content['description'] ?? '');

        $dtStart = $start !== '' ? $this->icsDate($start) : '';
        $dtEnd = $end !== '' ? $this->icsDate($end) : '';

        $lines = ['BEGIN:VEVENT', 'SUMMARY:' . $this->icsText($title)];
        if ($dtStart !== '') {
            $lines[] = 'DTSTART:' . $dtStart;
        }
        if ($dtEnd !== '') {
            $lines[] = 'DTEND:' . $dtEnd;
        }
        if ($location !== '') {
            $lines[] = 'LOCATION:' . $this->icsText($location);
        }
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $this->icsText($description);
        }
        $lines[] = 'END:VEVENT';

        return "BEGIN:VCALENDAR\nVERSION:2.0\n" . implode("\n", $lines) . "\nEND:VCALENDAR";
    }

    private function vcardPayload(array $content): string
    {
        $first = $this->string($content['first_name'] ?? '');
        $last = $this->string($content['last_name'] ?? '');

        if ($first === '' && $last === '') {
            return '';
        }

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . $this->icsText($last) . ';' . $this->icsText($first) . ';;;',
            'FN:' . $this->icsText(trim($first . ' ' . $last)),
        ];

        $map = [
            'organization' => 'ORG',
            'title' => 'TITLE',
            'email' => 'EMAIL',
            'phone_mobile' => 'TEL;TYPE=CELL',
            'phone_work' => 'TEL;TYPE=WORK',
            'website' => 'URL',
        ];

        foreach ($map as $field => $property) {
            $value = $this->string($content[$field] ?? '');
            if ($value !== '') {
                $lines[] = $property . ':' . $this->icsText($value);
            }
        }

        $street = $this->string($content['address_street'] ?? '');
        $city = $this->string($content['address_city'] ?? '');
        $zip = $this->string($content['address_zip'] ?? '');
        $country = $this->string($content['address_country'] ?? '');
        if ($street !== '' || $city !== '') {
            $lines[] = 'ADR;TYPE=HOME:;;' . $this->icsText($street) . ';' . $this->icsText($city) . ';' . $this->icsText($zip) . ';' . $this->icsText($country);
        }

        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    private function escapeWifi(string $value): string
    {
        return str_replace(['\\', '"', ';', ',', ':'], ['\\\\', '\\"', '\\;', '\\,', '\\:'], $value);
    }

    private function icsText(string $value): string
    {
        return str_replace(["\r\n", "\n", ',', ';'], ['\\n', '\\n', '\\,', '\\;'], $value);
    }

    private function icsDate(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp !== false ? gmdate('Ymd\THis\Z', $timestamp) : '';
    }
}
