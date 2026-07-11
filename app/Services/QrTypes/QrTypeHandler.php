<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

interface QrTypeHandler
{
    /**
     * Handle a QR code scan for a specific type.
     * Returns a response to send back to the scanner.
     */
    public function handle(QrCode $qrCode, Request $request): mixed;
}
