<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Password-reset flow (P2-T12 / Pflichtenheft §3.4.1).
 *
 * The reset mail is delivered as the queued, localisable {@see ResetPasswordMail}
 * Mailable instead of the framework's default notification, so this suite fakes
 * the mailer (rather than the notification dispatcher) and reads the reset token
 * straight off the sent Mailable.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Mail::assertQueued(ResetPasswordMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        $token = $this->resetTokenFromMail($user);

        $response = $this->get('/reset-password/'.$token);

        $response
            ->assertSeeVolt('pages.auth.reset-password')
            ->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        $token = $this->resetTokenFromMail($user);

        $component = Volt::test('pages.auth.reset-password', ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('resetPassword');

        $component
            ->assertRedirect('/login')
            ->assertHasNoErrors();
    }

    /**
     * Read the reset token off the sent {@see ResetPasswordMail} Mailable.
     */
    private function resetTokenFromMail(User $user): string
    {
        $token = null;

        Mail::assertQueued(ResetPasswordMail::class, function ($mail) use ($user, &$token) {
            $token = $mail->token;

            return $mail->hasTo($user->email);
        });

        return $token;
    }
}
