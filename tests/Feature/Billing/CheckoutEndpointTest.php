<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Stripe\StripeClient;
use Tests\Support\Billing\FakeStripeClient;
use Tests\TestCase;

class CheckoutEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        // Stripe price IDs configured (test mode).
        config([
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);

        // Swap Cashier's Stripe client for the in-process fake.
        $this->stripe = new FakeStripeClient;
        $this->app->bind(StripeClient::class, fn () => $this->stripe);
    }

    private function authenticate(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/billing/checkout', ['plan' => 'pro'])
            ->assertUnauthorized();
    }

    public function test_checkout_creates_session_for_pro_and_returns_url(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/billing/checkout', ['plan' => 'pro']);

        $response->assertOk()
            ->assertJsonStructure(['url', 'session_id', 'plan'])
            ->assertJsonPath('plan', 'pro');

        $this->assertNotEmpty($response->json('url'));
        $this->assertNotEmpty($response->json('session_id'));
    }

    public function test_checkout_creates_session_for_business(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'business'])
            ->assertOk()
            ->assertJsonPath('plan', 'business');
    }

    public function test_checkout_rejects_free_plan(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'free'])
            ->assertStatus(422);
    }

    public function test_checkout_rejects_missing_plan(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', [])
            ->assertStatus(422);
    }

    public function test_checkout_rejects_unknown_plan(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'enterprise'])
            ->assertStatus(422);
    }

    public function test_checkout_persists_stripe_customer_id_on_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['stripe_id' => null]);
        Sanctum::actingAs($user);

        $this->postJson('/api/billing/checkout', ['plan' => 'pro'])->assertOk();

        $this->assertNotNull($user->fresh()->stripe_id, 'stripe_id must be persisted');
        $this->assertNotEmpty($this->stripe->calls['customers.create'] ?? []);
    }

    public function test_checkout_reuses_existing_stripe_customer(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['stripe_id' => 'cus_existing']);
        Sanctum::actingAs($user);

        $this->postJson('/api/billing/checkout', ['plan' => 'pro'])->assertOk();

        $this->assertNotEmpty($this->stripe->calls['customers.retrieve'] ?? []);
        $this->assertSame('cus_existing', $user->fresh()->stripe_id);
    }

    public function test_checkout_passes_success_and_cancel_urls_to_stripe(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'pro'])->assertOk();

        $params = $this->stripe->calls['checkout.sessions.create'][0];

        $this->assertStringContainsString('checkout=success', $params['success_url']);
        $this->assertStringContainsString('plan=pro', $params['success_url']);
        $this->assertStringContainsString('checkout=cancelled', $params['cancel_url']);
        $this->assertSame('subscription', $params['mode']);
    }

    /**
     * The browser redirect is NOT proof of payment — the validated webhook
     * ([DEV-162](/DEV/issues/DEV-162)) is the single source of truth. Checkout
     * must therefore never mark the user's local plan as paid.
     */
    public function test_checkout_does_not_mutate_user_plan(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['plan' => 'free']);
        Sanctum::actingAs($user);

        $this->postJson('/api/billing/checkout', ['plan' => 'business'])->assertOk();

        $this->assertSame('free', $user->fresh()->plan, 'local plan must stay free until the webhook confirms');
    }

    public function test_checkout_uses_the_configured_price_for_the_plan(): void
    {
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'business'])->assertOk();

        $params = $this->stripe->calls['checkout.sessions.create'][0];
        $price = collect($params['line_items'] ?? [])->pluck('price')->first();

        $this->assertSame('price_business_test', $price);
    }

    public function test_checkout_returns_503_when_plan_price_is_not_configured(): void
    {
        config(['billing.plans.pro.price_id' => null]);
        $this->authenticate();

        $this->postJson('/api/billing/checkout', ['plan' => 'pro'])
            ->assertStatus(503);
    }
}
