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
 * Password-reset mail (P2-T12 / Pflichtenheft §3.4.1).
 *
 * Replaces the Starter-Kit's default {@see \Illuminate\Auth\Notifications\ResetPassword}
 * notification with a queued, localisable Mailable. The reset token is passed
 * straight through from the password broker and embedded only in the signed
 * reset link — never printed in clear text in the body.
 */
class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $resetUrl;

    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {
        $this->resetUrl = route(
            'password.reset',
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Reset Password Notification'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reset-password',
            with: [
                'userName' => $this->user->name,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
