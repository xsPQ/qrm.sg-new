<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Billing\PremiumAliasCheckoutController;
use App\Models\PremiumAliasPurchase;
use App\Models\User;
use App\Services\QrCodeRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Stripe\Checkout\Session as StripeCheckoutSession;

class PremiumAliasCheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.premium_alias.price_id' => 'price_premium_alias_test',
            'cashier.secret' => 'sk_test_premium_alias',
        ]);

        $this->app->instance(QrCodeRouteService::class, new class extends QrCodeRouteService
        {
            public function isAliasAvailable(string $alias, string $host, ?int $excludeId = null): bool
            {
                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_it_creates_a_pending_purchase_and_stripe_checkout_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $stripeSession = Mockery::mock('alias:' . StripeCheckoutSession::class);
        $stripeSession->shouldReceive('create')
            ->once()
            ->withArgs(function (array $params) use ($user): bool {
                $this->assertSame('payment', $params['mode']);
                $this->assertSame('price_premium_alias_test', $params['line_items'][0]['price']);
                $this->assertSame(1, $params['line_items'][0]['quantity']);
                $this->assertSame('premium_alias', $params['metadata']['type']);
                $this->assertSame('go', $params['metadata']['alias']);
                $this->assertSame((string) $user->id, $params['metadata']['user_id']);
                $this->assertSame($params['metadata']['purchase_id'], $params['client_reference_id']);
                $this->assertStringContainsString('/billing/premium-alias/', $params['success_url']);
                $this->assertStringContainsString('/success', $params['success_url']);
                $this->assertStringContainsString('/cancel', $params['cancel_url']);
                $this->assertMatchesRegularExpression('/^\d+$/', (string) $params['metadata']['purchase_id']);

                return true;
            })
            ->andReturn((object) [
                'id' => 'cs_test_alias_123',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_alias_123',
            ]);

        $response = $this->postJson('/billing/premium-alias/checkout', ['alias' => 'Go']);

        $response->assertOk()
            ->assertJsonPath('alias', 'go')
            ->assertJsonPath('session_id', 'cs_test_alias_123')
            ->assertJsonPath('url', 'https://checkout.stripe.com/c/pay/cs_test_alias_123');

        $purchaseId = $response->json('purchase_id');

        $this->assertIsInt($purchaseId);
        $this->assertDatabaseHas('premium_alias_purchases', [
            'id' => $purchaseId,
            'user_id' => $user->id,
            'alias' => 'go',
            'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_alias_123',
        ]);
    }

    public function test_it_returns_already_owned_when_the_current_user_already_bought_the_alias(): void
    {
        $user = User::factory()->create();
        PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => 'go',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/billing/premium-alias/checkout', ['alias' => 'GO']);

        $response->assertOk()
            ->assertJson([
                'message' => 'You already own this alias.',
                'status' => 'already_owned',
            ]);

        $this->assertDatabaseCount('premium_alias_purchases', 1);
    }

    public function test_it_returns_taken_when_the_alias_was_already_purchased_by_someone_else(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        PremiumAliasPurchase::create([
            'user_id' => $otherUser->id,
            'alias' => 'go',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/billing/premium-alias/checkout', ['alias' => 'go']);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'This alias is already taken.',
                'status' => 'taken',
            ]);

        $this->assertDatabaseCount('premium_alias_purchases', 1);
    }

    public function test_it_rejects_aliases_longer_than_four_characters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/billing/premium-alias/checkout', ['alias' => 'toolong']);

        $response->assertSessionHasErrors(['alias']);
    }
}
