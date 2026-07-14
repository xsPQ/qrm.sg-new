<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Upgrade-Hinweis-Mail (P2-T12 / Pflichtenheft §2.4): "Nach 10 QR-Codes →
 * Upgrade-Prompt". Sent when a Free user reaches the active-QR limit.
 *
 * No secrets or PII beyond the recipient's own plan status are placed in the
 * body.
 */
class FreeTierUpgradeHintMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $activeCount,
        public readonly int $limit,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You have reached your Free plan limit'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.free-tier-upgrade-hint',
            with: [
                'userName' => $this->user->name,
                'activeCount' => $this->activeCount,
                'limit' => $this->limit,
                'upgradeUrl' => route('dashboard'),
            ],
        );
    }
}
