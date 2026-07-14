<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QrCodeExpiringSoon;
use App\Mail\QrCodeExpiringSoonMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the Ablauf-Warnungs-Mail when a Free QR-code enters the pre-expiry
 * warning window (P2-T12 / Pflichtenheft §2.4).
 *
 * The scheduler fires {@see \App\Events\QrCodeExpiringSoon} once per day for each
 * code in the window. To avoid sending the warning repeatedly over the remaining
 * lifetime of the code, the mail is sent at most once per code+expiry via a
 * cache key. Listeners own their own idempotency.
 *
 * The Mailable itself is queued (ShouldQueue), so actual delivery runs through
 * the queue (P1-T06).
 */
class SendQrCodeExpiryWarning implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(QrCodeExpiringSoon $event): void
    {
        $qrCode = $event->qrCode;
        $user = $qrCode->user;

        if ($user === null) {
            return;
        }

        $cacheKey = $this->cacheKey($qrCode->id, (string) $qrCode->expires_at);

        if (Cache::has($cacheKey)) {
            return;
        }

        $warningDays = (int) config('qr.free.expiry_warning_days', 3);

        Mail::to($user)->send(new QrCodeExpiringSoonMail(
            $qrCode,
            (int) $qrCode->expiresInDays(),
            $warningDays,
        ));

        // Idempotency: hold the key for the configured warning window plus a
        // margin so the warning is not re-sent while the code is still active.
        Cache::put($cacheKey, true, now()->addDays(max(1, $warningDays) + 1));
    }

    private function cacheKey(int $qrCodeId, string $expiresAt): string
    {
        return 'mail:qr-expiring-warning:'.$qrCodeId.':'.sha1($expiresAt);
    }
}
