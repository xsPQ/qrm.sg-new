<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\QrCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for each active Free QR-code that enters the pre-expiry warning
 * window (Pflichtenheft §2.4: "3 Tage vor Ablauf → E-Mail mit Pro-Verweis").
 *
 * This event is the extensible hook for the expiry-warning email (P2-T12).
 * It is fired by {@see QrCode::dispatchExpiringSoonEvents()} from
 * the scheduler. Listeners (e.g. a future SendExpiryWarningEmail listener)
 * are responsible for their own idempotency. Email delivery itself is out of
 * scope here and tracked by P2-T12.
 */
class QrCodeExpiringSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly QrCode $qrCode,
    ) {}
}
