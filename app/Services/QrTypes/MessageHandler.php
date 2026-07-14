<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

class MessageHandler implements QrTypeHandler
{
    /**
     * Display a message to the scanner.
     */
    public function handle(QrCode $qrCode, Request $request): mixed
    {
        $title = $qrCode->content['title'] ?? $qrCode->title ?? 'Message';
        $body = $qrCode->content['body'] ?? $qrCode->content['message'] ?? '';
        $theme = $qrCode->settings['theme'] ?? 'light';

        return view('qr-types.message', [
            'qrCode' => $qrCode,
            'title' => $title,
            'body' => $body,
            'theme' => $theme,
        ]);
    }
}
