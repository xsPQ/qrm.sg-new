<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Stripe\StripeClient;
use Tests\Support\Billing\FakeStripeClient;
use Tests\TestCase;

class CustomerPortalEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);

        $this->stripe = new FakeStripeClient;
        $this->app->bind(StripeClient::class, fn () => $this->stripe);
    }

    private function authenticate(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_portal_requires_authentication(): void
    {
        $this->postJson('/api/billing/portal')
            ->assertUnauthorized();
    }

    public function test_portal_returns_url_for_existing_customer(): void
    {
        $this->authenticate(['stripe_id' => 'cus_existing']);

        $response = $this->postJson('/api/billing/portal');

        $response->assertOk()
            ->assertJsonStructure(['url'])
            ->assertJsonPath('url', fn ($url) => is_string($url) && $url !== '');
    }

    public function test_portal_passes_return_url_to_stripe(): void
    {
        $this->authenticate(['stripe_id' => 'cus_existing']);

        $this->postJson('/api/billing/portal')->assertOk();

        $params = $this->stripe->calls['billingPortal.sessions.create'][0];

        $this->assertSame('cus_existing', $params['customer']);
        $this->assertNotNull($params['return_url']);
    }

    public function test_portal_returns_404_when_user_has_no_stripe_customer(): void
    {
        $this->authenticate(['stripe_id' => null]);

        $this->postJson('/api/billing/portal')
            ->assertStatus(404);
    }

    public function test_portal_does_not_mutate_user_plan(): void
    {
        $this->authenticate(['stripe_id' => 'cus_existing', 'plan' => 'free']);

        $this->postJson('/api/billing/portal')->assertOk();

        $this->assertSame('free', User::first()->plan);
    }
}
