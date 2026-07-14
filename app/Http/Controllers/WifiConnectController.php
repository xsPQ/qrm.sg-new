<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WifiConnectController extends Controller
{
    /**
     * Generates an Apple .mobileconfig profile for WiFi network installation.
     * iOS/iPadOS opens this in Settings → "Profile Downloaded" → Install.
     */
    public function appleProfile(Request $request, string $code): Response
    {
        $qrCode = $this->resolveQrCode($code);
        if (! $qrCode || $qrCode->type !== 'wifi') {
            abort(404);
        }

        $ssid = $qrCode->content['ssid'] ?? '';
        $encryption = $qrCode->content['encryption'] ?? 'WPA';
        $password = $qrCode->content['password'] ?? '';

        $encryptionType = match (strtoupper($encryption)) {
            'WPA', 'WPA2' => 'WPA',
            'WEP' => 'WEP',
            default => 'None',
        };

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $payloadName = e($ssid) . ' — qrm.sg';

        $config = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $config .= '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n";
        $config .= '<plist version="1.0">' . "\n";
        $config .= '<dict>' . "\n";
        $config .= '  <key>PayloadContent</key>' . "\n";
        $config .= '  <array>' . "\n";
        $config .= '    <dict>' . "\n";
        $config .= '      <key>PayloadType</key>' . "\n";
        $config .= '      <string>com.apple.wifi.managed</string>' . "\n";
        $config .= '      <key>PayloadVersion</key>' . "\n";
        $config .= '      <integer>1</integer>' . "\n";
        $config .= '      <key>PayloadIdentifier</key>' . "\n";
        $config .= '      <string>sg.qrm.wifi.' . $uuid . '</string>' . "\n";
        $config .= '      <key>PayloadUUID</key>' . "\n";
        $config .= '      <string>' . $uuid . '</string>' . "\n";
        $config .= '      <key>SSID_STR</key>' . "\n";
        $config .= '      <string>' . htmlspecialchars($ssid, ENT_XML1) . '</string>' . "\n";
        $config .= '      <key>EncryptionType</key>' . "\n";
        $config .= '      <string>' . $encryptionType . '</string>' . "\n";
        if ($encryptionType !== 'None' && $password) {
            $config .= '      <key>Password</key>' . "\n";
            $config .= '      <string>' . htmlspecialchars($password, ENT_XML1) . '</string>' . "\n";
        }
        if (! empty($qrCode->content['hidden'])) {
            $config .= '      <key>HIDDEN_NETWORK</key>' . "\n";
            $config .= '      <true/>' . "\n";
        }
        $config .= '      <key>AutoJoin</key>' . "\n";
        $config .= '      <true/>' . "\n";
        $config .= '    </dict>' . "\n";
        $config .= '  </array>' . "\n";
        $config .= '  <key>PayloadDisplayName</key>' . "\n";
        $config .= '  <string>' . htmlspecialchars($payloadName, ENT_XML1) . '</string>' . "\n";
        $config .= '  <key>PayloadIdentifier</key>' . "\n";
        $config .= '  <string>sg.qrm.wifi</string>' . "\n";
        $config .= '  <key>PayloadType</key>' . "\n";
        $config .= '  <string>Configuration</string>' . "\n";
        $config .= '  <key>PayloadUUID</key>' . "\n";
        $config .= '  <string>' . $uuid . '-root</string>' . "\n";
        $config .= '  <key>PayloadVersion</key>' . "\n";
        $config .= '  <integer>1</integer>' . "\n";
        $config .= '</dict>' . "\n";
        $config .= '</plist>' . "\n";

        return new Response($config, 200, [
            'Content-Type' => 'application/x-apple-aspen-config',
            'Content-Disposition' => 'attachment; filename="' . sanitizeFilename($ssid) . '.mobileconfig"',
        ]);
    }

    /**
     * Returns the WiFi URI scheme (android:special / WIFI: format).
     * Android can trigger this to open WiFi settings pre-filled.
     */
    public function wifiUri(Request $request, string $code): Response
    {
        $qrCode = $this->resolveQrCode($code);
        if (! $qrCode || $qrCode->type !== 'wifi') {
            abort(404);
        }

        $ssid = $qrCode->content['ssid'] ?? '';
        $encryption = strtoupper($qrCode->content['encryption'] ?? 'WPA');
        $password = $qrCode->content['password'] ?? '';
        $hidden = ! empty($qrCode->content['hidden']);

        $encryptionCode = match ($encryption) {
            'WPA', 'WPA2' => 'WPA',
            'WEP' => 'WEP',
            default => 'nopass',
        };

        // Standard WIFI: URI scheme (RFC for QR codes)
        $uri = 'WIFI:S:' . escapeWifiToken($ssid) . ';';
        $uri .= 'T:' . $encryptionCode . ';';
        if ($password && $encryptionCode !== 'nopass') {
            $uri .= 'P:' . escapeWifiToken($password) . ';';
        }
        if ($hidden) {
            $uri .= 'H:true;';
        }

        return response($uri, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function resolveQrCode(string $code): ?QrCode
    {
        $route = QrCodeRoute::where('code', $code)->first();

        return $route?->qrCode;
    }
}

/**
 * Escapes special characters in WiFi URI tokens per the WIFI: format spec.
 */
function escapeWifiToken(string $token): string
{
    return str_replace(
        ['\\', ';', ',', ':', '"'],
        ['\\\\', '\\;', '\\,', '\\:', '\\"'],
        $token
    );
}

/**
 * Sanitizes an SSID for use as a filename.
 */
function sanitizeFilename(string $input): string
{
    $clean = preg_replace('/[^A-Za-z0-9\-_]/', '_', $input);
    return $clean ?: 'wifi';
}
