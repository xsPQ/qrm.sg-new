<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\QrCode;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\Billing\StripeSignature;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Activate Cashier's signature middleware + price map.
        config([
            'cashier.webhook.secret' => self::SECRET,
            'cashier.webhook.tolerance' => 300,
            'billing.plans.pro.price_id' => 'price_pro_test',
            'billing.plans.business.price_id' => 'price_business_test',
        ]);

        $this->user = User::factory()->create([
            'stripe_id' => 'cus_test_user',
            'plan' => 'free',
        ]);
    }

    public function test_webhook_rejects_an_invalid_signature(): void
    {
        $this->postSigned('checkout.session.completed', ['customer' => 'cus_test_user'], 'evt_x', 't='.time().',v1=deadbeef')
            ->assertForbidden();
    }

    public function test_webhook_rejects_a_missing_signature(): void
    {
        $this->postRaw($this->payload('checkout.session.completed', ['customer' => 'cus_test_user'], 'evt_x'))
            ->assertForbidden();
    }

    public function test_webhook_rejects_a_replayed_old_timestamp(): void
    {
        $body = $this->payload('checkout.session.completed', ['customer' => 'cus_test_user'], 'evt_x');
        $oldSignature = StripeSignature::header($body, self::SECRET, time() - 100000);

        $this->postRaw($body, $oldSignature)->assertForbidden();
    }

    public function test_checkout_session_completed_activates_the_plan(): void
    {
        $this->seedActiveSubscription('price_pro_test');

        $this->postSigned('checkout.session.completed', ['customer' => 'cus_test_user'], 'evt_checkout_ok')
            ->assertOk();

        $this->assertSame('pro', $this->user->fresh()->plan);
    }

    public function test_subscription_updated_activates_the_plan_from_status_and_price(): void
    {
        $object = $this->subscriptionObject('sub_updated_1', 'active', 'price_business_test');

        $this->postSigned('customer.subscription.updated', $object, 'evt_sub_updated')
            ->assertOk();

        $this->assertSame('business', $this->user->fresh()->plan);
    }

    public function test_subscription_updated_with_non_granting_status_downgrades_to_free(): void
    {
        $this->setPlan('pro');

        $object = $this->subscriptionObject('sub_updated_2', 'past_due', 'price_pro_test', 'si_2');

        $this->postSigned('customer.subscription.updated', $object, 'evt_sub_pastdue')->assertOk();

        $this->assertSame('free', $this->user->fresh()->plan);
    }

    public function test_subscription_deleted_downgrades_to_free(): void
    {
        $this->setPlan('pro');
        $this->seedActiveSubscription('price_pro_test', 'sub_deleted_1');

        $this->postSigned('customer.subscription.deleted', [
            'customer' => 'cus_test_user',
            'id' => 'sub_deleted_1',
        ], 'evt_sub_deleted')->assertOk();

        $this->assertSame('free', $this->user->fresh()->plan);
    }

    public function test_invoice_paid_syncs_the_plan(): void
    {
        $this->seedActiveSubscription('price_pro_test');

        $this->postSigned('invoice.paid', [
            'customer' => 'cus_test_user',
            'subscription' => 'sub_seed_1',
        ], 'evt_invoice_paid')->assertOk();

        $this->assertSame('pro', $this->user->fresh()->plan);
    }

    public function test_invoice_payment_failed_is_logged_and_leaves_plan_unchanged(): void
    {
        $this->setPlan('pro');

        $this->postSigned('invoice.payment_failed', [
            'customer' => 'cus_test_user',
            'subscription' => 'sub_some',
        ], 'evt_invoice_failed')->assertOk();

        $this->assertSame('pro', $this->user->fresh()->plan);
    }

    public function test_the_same_event_id_is_never_processed_twice(): void
    {
        $object = $this->subscriptionObject('sub_dup', 'active', 'price_pro_test', 'si_dup');

        // First delivery activates Pro.
        $this->postSigned('customer.subscription.updated', $object, 'evt_duplicate')->assertOk();
        $this->assertSame('pro', $this->user->fresh()->plan);

        // Revert the plan to prove the second delivery is a true no-op.
        $this->setPlan('free');

        // Second delivery of the same event id must be deduped.
        $this->postSigned('customer.subscription.updated', $object, 'evt_duplicate')->assertOk();
        $this->assertSame('free', $this->user->fresh()->plan, 'duplicate event must not be reprocessed');

        $this->assertSame(1, StripeWebhookEvent::where('stripe_event_id', 'evt_duplicate')->count());
    }

    /**
     * Invariant (P2-T08 / Pflichtenheft §3.5.3): a plan change from the webhook
     * must NEVER overwrite a QR-code's `entitlement_snapshot` (Bestandsschutz).
     */
    public function test_plan_change_does_not_touch_entitlement_snapshots(): void
    {
        $qrCodeId = DB::table('qr_codes')->insertGetId([
            'user_id' => $this->user->id,
            'title' => 'Grandfathered code',
            'type' => 'url',
            'content' => json_encode(['url' => 'https://example.com']),
            'status' => 'active',
            'entitlement_snapshot' => json_encode(['version' => 1, 'plan' => 'business', 'tier' => 'business']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $snapshotBefore = DB::table('qr_codes')->where('id', $qrCodeId)->value('entitlement_snapshot');

        // Activate Pro via the webhook.
        $object = $this->subscriptionObject('sub_snap', 'active', 'price_pro_test', 'si_snap');
        $this->postSigned('customer.subscription.updated', $object, 'evt_snapshot')->assertOk();

        $this->assertSame('pro', $this->user->fresh()->plan);
        $this->assertSame(
            $snapshotBefore,
            DB::table('qr_codes')->where('id', $qrCodeId)->value('entitlement_snapshot'),
            'entitlement_snapshot must remain unchanged by a webhook plan update'
        );

        // Touching the model and re-saving must not have been triggered either.
        $this->assertSame('business', QrCode::find($qrCodeId)->entitlement_snapshot['plan'] ?? null);
    }

    /**
     * Build the JSON body for an event.
     */
    private function payload(string $type, array $object, string $eventId): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => $type,
            'data' => ['object' => $object],
        ], JSON_THROW_ON_ERROR);
    }

    private function postSigned(string $type, array $object, string $eventId, ?string $signature = null): TestResponse
    {
        $body = $this->payload($type, $object, $eventId);

        return $this->postRaw($body, $signature ?? StripeSignature::header($body, self::SECRET));
    }

    private function postRaw(string $body, ?string $signature = null): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($signature !== null) {
            $server['HTTP_STRIPE_SIGNATURE'] = $signature;
        }

        return $this->call('POST', '/api/billing/webhook', [], [], [], $server, $body);
    }

    private function seedActiveSubscription(string $priceId, string $stripeId = 'sub_seed_1'): void
    {
        $this->user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);
    }

    private function setPlan(string $plan): void
    {
        $user = $this->user->fresh();
        $user->plan = $plan;
        $user->save();
    }

    /**
     * Build a Stripe subscription object payload with the real item shape.
     */
    private function subscriptionObject(string $stripeId, string $status, string $priceId, string $itemId = 'si_1'): array
    {
        return [
            'customer' => 'cus_test_user',
            'id' => $stripeId,
            'status' => $status,
            'items' => ['data' => [
                ['id' => $itemId, 'price' => ['id' => $priceId, 'product' => 'prod_1'], 'quantity' => 1],
            ]],
        ];
    }
}
