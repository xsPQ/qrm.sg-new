<?php

namespace Tests\Feature;

use App\Enums\UserPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Account page (P2-T05 / [DEV-157](/DEV/issues/DEV-157)).
 *
 * Covers the acceptance criteria: profile update, password change requiring the
 * current password, email-verification status/trigger, tariff display per plan
 * and a working Billing/Upgrade link.
 */
class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.plan-card')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_account_page_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_account_route_is_named(): void
    {
        $this->assertSame(url('/account'), route('account'));
    }

    public function test_profile_information_can_be_updated_from_account_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->call('updateProfileInformation');

        $component->assertHasNoErrors()->assertNoRedirect();

        $this->assertSame('Jane Doe', $user->refresh()->name);
        $this->assertSame('jane@example.com', $user->refresh()->email);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');
    }

    public function test_unverified_email_status_is_shown_and_verification_can_be_triggered(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form');

        $component->assertSee(__('Your email address is unverified.'));

        // Triggering the verification link when already unverified sends the
        // notification and flashes the sent-status, without erroring.
        $component->call('sendVerification')->assertHasNoErrors();
    }

    public function test_plan_card_renders_the_current_plan_for_each_tier(): void
    {
        foreach (UserPlan::cases() as $plan) {
            $user = User::factory()->create(['plan' => $plan->value]);

            $this->actingAs($user);

            Volt::test('profile.plan-card')
                ->assertSee($plan->label());
        }
    }

    public function test_plan_card_shows_upgrade_links_for_free_users(): void
    {
        $user = User::factory()->create(['plan' => UserPlan::Free->value]);

        $this->actingAs($user);

        Volt::test('profile.plan-card')
            ->assertSee(__('Upgrade to :plan', ['plan' => 'Pro']))
            ->assertSee(__('Upgrade to :plan', ['plan' => 'Business']))
            ->assertDontSee(__('Manage Subscription'));
    }

    public function test_plan_card_shows_manage_subscription_for_paid_users(): void
    {
        $user = User::factory()->create(['plan' => UserPlan::Business->value]);

        $this->actingAs($user);

        Volt::test('profile.plan-card')
            ->assertSee(__('Manage Subscription'))
            ->assertDontSee(__('Upgrade to :plan', ['plan' => 'Pro']));
    }

    public function test_account_page_renders_a_billing_link(): void
    {
        $user = User::factory()->create(['plan' => UserPlan::Free->value]);

        $response = $this->actingAs($user)->get('/account');

        $response->assertSee(route('billing.web.checkout', ['plan' => 'pro']));
    }

    public function test_billing_checkout_rejects_free_plan_target(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/account')
            ->actingAs($user)
            ->post('/billing/checkout/free');

        $response
            ->assertRedirect('/account')
            ->assertSessionHas('billing_error');
    }

    public function test_billing_checkout_handles_unconfigured_stripe_gracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/account')
            ->actingAs($user)
            ->post('/billing/checkout/pro');

        $response
            ->assertRedirect('/account')
            ->assertSessionHas('billing_error');
    }

    public function test_billing_portal_handles_missing_customer_gracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/account')
            ->actingAs($user)
            ->post('/billing/portal');

        $response
            ->assertRedirect('/account')
            ->assertSessionHas('billing_error');
    }

    public function test_billing_web_routes_require_authentication(): void
    {
        $this->post('/billing/checkout/pro')->assertRedirect('/login');
        $this->post('/billing/portal')->assertRedirect('/login');
    }
}
