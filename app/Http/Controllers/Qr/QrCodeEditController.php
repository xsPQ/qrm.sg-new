<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;

/**
 * QR-Code edit page (Pflichtenheft §3.6.1 / §5.1, P2-T03).
 *
 * Authenticated single-code editor reachable from the dashboard list (P2-T01)
 * and the detail page (P2-T02). Access is restricted to the code's owner and
 * admins through the QrCodePolicy `update` check (P1-T15). The actual
 * type-specific form, validation, alias handling and delete-with-confirmation
 * live in the {@see \App\Livewire\QrCodeEditor} Livewire component, which keeps
 * the controller thin and mirrors the Creator (P2-T04) wiring.
 */
class QrCodeEditController extends Controller
{
    public function edit(Request $request, QrCode $qrCode)
    {
        // Owner/admin gate (P1-T15). Non-owners receive 403.
        $this->authorize('update', $qrCode);

        $qrCode->loadMissing('route');

        return view('qr-codes.edit', [
            'qrCode' => $qrCode,
        ]);
    }
}
