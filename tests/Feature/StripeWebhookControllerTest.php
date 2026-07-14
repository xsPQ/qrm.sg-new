<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PremiumAliasPurchase;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Billing\StripeSignature;
use Tests\TestCase;

class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cashier.webhook.secret' => self::WEBHOOK_SECRET,
            'cashier.webhook.tolerance' => 300,
            'cashier.secret' => 'sk_test_webhook_secret',
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);

        $this->user = User::factory()->create([
            'stripe_id' => 'cus_test_user',
            'plan' => 'free',
        ]);
    }

    public function test_checkout_session_completed_syncs_the_subscription_plan(): void
    {
        $this->user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_checkout',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
        ]);

        $this->postSignedEvent('checkout.session.completed', [
            'customer' => 'cus_test_user',
        ], 'evt_checkout_subscription')->assertOk();

        $this->assertSame('pro', $this->user->fresh()->plan);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_checkout_subscription',
            'type' => 'checkout.session.completed',
        ]);
    }

    public function test_checkout_session_completed_marks_premium_alias_purchase_paid_from_metadata(): void
    {
        $purchase = PremiumAliasPurchase::create([
            'user_id' => $this->user->id,
            'alias' => 'go',
            'status' => 'pending',
        ]);

        $this->user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_alias',
            'stripe_status' => 'active',
            'stripe_price' => 'price_business_test',
            'quantity' => 1,
        ]);

        $this->postSignedEvent('checkout.session.completed', [
            'customer' => 'cus_test_user',
            'payment_intent' => 'pi_test_alias_123',
            'metadata' => [
                'type' => 'premium_alias',
                'alias' => 'go',
                'purchase_id' => (string) $purchase->id,
                'user_id' => (string) $this->user->id,
            ],
        ], 'evt_checkout_alias')->assertOk();

        $purchase->refresh();

        $this->assertSame('paid', $purchase->status);
        $this->assertNotNull($purchase->paid_at);
        $this->assertSame('pi_test_alias_123', $purchase->stripe_payment_intent_id);
        $this->assertSame('business', $this->user->fresh()->plan);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_checkout_alias',
            'type' => 'checkout.session.completed',
        ]);
    }

    public function test_customer_subscription_deleted_downgrades_the_account_to_free(): void
    {
        $this->user->update(['plan' => 'pro']);

        $this->postSignedEvent('customer.subscription.deleted', [
            'customer' => 'cus_test_user',
            'id' => 'sub_deleted_test',
        ], 'evt_subscription_deleted')->assertOk();

        $this->assertSame('free', $this->user->fresh()->plan);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_subscription_deleted',
            'type' => 'customer.subscription.deleted',
        ]);
    }

    public function test_customer_subscription_updated_syncs_the_plan_from_status_and_price(): void
    {
        $this->postSignedEvent('customer.subscription.updated', [
            'customer' => 'cus_test_user',
            'id' => 'sub_updated_test',
            'status' => 'active',
            'items' => [
                'data' => [
                    [
                        'id' => 'si_updated_test',
                        'price' => [
                            'id' => 'price_business_test',
                            'product' => 'prod_business_test',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ], 'evt_subscription_updated')->assertOk();

        $this->assertSame('business', $this->user->fresh()->plan);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'stripe_event_id' => 'evt_subscription_updated',
            'type' => 'customer.subscription.updated',
        ]);
    }

    private function postSignedEvent(string $type, array $object, string $eventId)
    {
        $body = $this->payload($type, $object, $eventId);

        return $this->call(
            'POST',
            '/api/billing/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => StripeSignature::header($body, self::WEBHOOK_SECRET),
            ],
            $body,
        );
    }

    private function payload(string $type, array $object, string $eventId): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => $type,
            'data' => [
                'object' => $object,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
