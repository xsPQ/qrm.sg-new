<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ablauf-Warnungs-Mail (P2-T12 / Pflichtenheft §2.4): "3 Tage vor Ablauf → E-Mail
 * mit Pro-Verweis". Sent when a Free QR-code enters the pre-expiry warning window.
 *
 * Only the code's title, the days remaining and an upgrade reference are placed
 * in the body. The QR-code content (which may carry PII, e.g. vCard/contact) is
 * intentionally never included.
 */
class QrCodeExpiringSoonMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly QrCode $qrCode,
        public readonly int $daysRemaining,
        public readonly int $warningDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your QR code is expiring soon'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.qr-code-expiring-soon',
            with: [
                'userName' => $this->qrCode->user?->name,
                'qrTitle' => $this->qrCode->title,
                'daysRemaining' => $this->daysRemaining,
                'warningDays' => $this->warningDays,
                'expiresAt' => $this->qrCode->expires_at,
                'upgradeUrl' => route('dashboard'),
            ],
        );
    }
}
