<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodeRoute;
use App\Services\QrCodeResolver;
use Illuminate\Http\Request;

class QrScanController extends Controller
{
    public function __construct(
        private QrCodeResolver $resolver,
    ) {}

    /**
     * Handle a QR code scan via its short code or alias.
     * GET /r/{code}
     */
    public function resolve(string $code, Request $request): mixed
    {
        $route = QrCodeRoute::where('code', $code)
            ->orWhere('alias', $code)
            ->first();

        if (!$route) {
            return view('qr-types.error', [
                'qrCode' => null,
                'reason' => 'not_found',
            ]);
        }

        $qrCode = $route->qrCode()->first();

        if (!$qrCode) {
            return view('qr-types.error', [
                'qrCode' => null,
                'reason' => 'not_found',
            ]);
        }

        return $this->resolver->resolve($qrCode, $request);
    }

    /**
     * Submit a password for a password-protected QR code.
     * POST /r/{code}/password
     */
    public function password(string $code, Request $request): mixed
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $route = QrCodeRoute::where('code', $code)
            ->orWhere('alias', $code)
            ->first();

        if (!$route) {
            return back()->withErrors(['code' => 'QR code not found.']);
        }

        $qrCode = $route->qrCode;

        if (!$qrCode->password_hash) {
            return redirect()->route('qr.resolve', $code);
        }

        if (!password_verify($request->input('password'), $qrCode->password_hash)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $cookieName = 'qr_password_' . $qrCode->id;
        $token = hash_hmac('sha256', $qrCode->id . $qrCode->password_hash, config('app.key'));

        return redirect()
            ->route('qr.resolve', $code)
            ->withCookie(cookie($cookieName, $token, 60));
    }
}
