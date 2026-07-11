<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Events\FreeTierLimitReached;
use App\Mail\FreeTierUpgradeHintMail;
use App\Mail\QrCodeExpiringSoonMail;
use App\Mail\VerifyEmailMail;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * E-Mail-Flow coverage (P2-T12 / Pflichtenheft §3.4.1, §2.4).
 *
 * Mail-fake tests for the Verify, Ablauf-Warnung (3 Tage vor expires_at) and
 * Free-Limit Upgrade-Hinweis flows. The Reset flow is covered by
 * {@see \Tests\Feature\Auth\PasswordResetTest}. Delivery is queued (the
 * Mailables implement ShouldQueue); in the testing env the queue is
 * synchronous and the mailer is faked (phpunit.xml), so assertions run inline.
 */
class EmailFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_mail_is_sent_when_verification_is_requested(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();

        Mail::assertQueued(VerifyEmailMail::class, function (VerifyEmailMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && str_contains($mail->verificationUrl, (string) $user->getKey());
        });
    }

    public function test_expiring_soon_mail_sent_for_free_code_in_warning_window(): void
    {
        Mail::fake();
        Carbon::setTestNow(now());

        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Expiring soon',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->addDays(2),
        ]);
        $qrCode->entitlement_snapshot = ['tier' => 'free'];
        $qrCode->save();

        $dispatched = QrCode::dispatchExpiringSoonEvents();

        $this->assertSame(1, $dispatched, 'Exactly one Free code is in the warning window.');

        Mail::assertQueued(QrCodeExpiringSoonMail::class, function (QrCodeExpiringSoonMail $mail) use ($user, $qrCode): bool {
            return $mail->hasTo($user->email)
                && $mail->qrCode->is($qrCode)
                && $mail->daysRemaining === 1
                && $mail->warningDays === (int) config('qr.free.expiry_warning_days', 3);
        });

        Carbon::setTestNow(null);
    }

    public function test_expiring_soon_mail_not_sent_outside_warning_window(): void
    {
        Mail::fake();
        Carbon::setTestNow(now());

        $user = User::factory()->create();

        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'title' => 'Far future',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'status' => 'active',
            'expires_at' => now()->addDays(20),
        ]);
        $qrCode->entitlement_snapshot = ['tier' => 'free'];
        $qrCode->save();

        $this->assertSame(0, QrCode::dispatchExpiringSoonEvents());

        Mail::assertNotQueued(QrCodeExpiringSoonMail::class);

        Carbon::setTestNow(null);
    }

    public function test_free_limit_upgrade_hint_mail_sent_when_limit_reached(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        event(new FreeTierLimitReached($user, 10, 10));

        Mail::assertQueued(FreeTierUpgradeHintMail::class, function (FreeTierUpgradeHintMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && $mail->activeCount === 10
                && $mail->limit === 10;
        });
    }

    public function test_free_limit_upgrade_hint_is_throttled_to_once_per_window(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        // The enforcement hook fires on every at-limit create attempt; the
        // listener must not spam the user. Repeated dispatches collapse to one.
        event(new FreeTierLimitReached($user, 10, 10));
        event(new FreeTierLimitReached($user, 10, 10));

        Mail::assertQueued(FreeTierUpgradeHintMail::class, 1);
    }
}
