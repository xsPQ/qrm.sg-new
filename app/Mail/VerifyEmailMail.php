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
use Illuminate\Support\Facades\URL;

/**
 * Email-verification mail (P2-T12 / Pflichtenheft §3.4.1).
 *
 * Replaces the Starter-Kit's default {@see \Illuminate\Auth\Notifications\VerifyEmail}
 * notification with a queued, localisable Mailable. The signed verification URL
 * is derived exactly like the framework default, so the existing verify route and
 * controller are unchanged.
 *
 * No secrets or PII beyond the recipient's own verification link are placed in
 * the body.
 */
class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $verificationUrl;

    public function __construct(public readonly User $user)
    {
        $this->verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Verify Email Address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verify-email',
            with: [
                'userName' => $this->user->name,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }
}
