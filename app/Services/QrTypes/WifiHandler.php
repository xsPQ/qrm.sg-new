<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

class WifiHandler implements QrTypeHandler
{
    public function handle(QrCode $qrCode, Request $request): mixed
    {
        $ssid = $qrCode->content['ssid'] ?? '';
        $encryption = $qrCode->content['encryption'] ?? 'WPA';
        $password = $qrCode->content['password'] ?? null;
        $hidden = $qrCode->content['hidden'] ?? false;

        return view('qr-types.wifi', [
            'qrCode' => $qrCode,
            'ssid' => $ssid,
            'encryption' => $encryption,
            'password' => $password,
            'hidden' => $hidden,
        ]);
    }
}
