<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\StripeWebhookEvent;
use App\Services\Billing\AccountPlanSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe webhook endpoint (Pflichtenheft §3.5.1, P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * <code>POST /api/billing/webhook</code> — public, protected by the Stripe
 * signature (Cashier's {@see VerifyWebhookSignature} middleware). Extends
 * Cashier's controller to add:
 *
 *  - **Signature verification + replay protection** — inherited from Cashier
 *    (Stripe-Signature header + tolerance window).
 *  - **Idempotency** — every processed Stripe event id is recorded in
 *    {@see StripeWebhookEvent}; duplicate deliveries are acknowledged without
 *    re-processing.
 *  - **Plan sync (single source of truth)** — the account `plan`/tier field is
 *    updated exclusively from these events, never from a Checkout redirect.
 *
 * Invariant (P2-T08 / Pflichtenheft §3.5.3): the plan sync touches only the
 * account field; it never overwrites a QR-code's `entitlement_snapshot`
 * (grandfathering / Bestandsschutz).
 */
class StripeWebhookController extends CashierWebhookController
{
    public function __construct(private readonly AccountPlanSync $planSync)
    {
        parent::__construct();
    }

    /**
     * Dispatch the event with idempotency on the Stripe event id.
     *
     * {@inheritdoc}
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $this->decode($request);
        $eventId = is_array($payload) ? ($payload['id'] ?? null) : null;

        if (is_string($eventId) && $this->alreadyProcessed($eventId)) {
            Log::info('Stripe webhook duplicate event ignored.', [
                'stripe_event_id' => $eventId,
            ]);

            return $this->successMethod();
        }

        $response = parent::handleWebhook($request);

        if (is_string($eventId)) {
            $this->markProcessed($eventId, is_array($payload) ? ($payload['type'] ?? null) : null);
        }

        return $response;
    }

    /**
     * checkout.session.completed — payment confirmed; activate the plan.
     *
     * The session payload carries the Stripe customer (and subscription id).
     * The matching subscription row has already been synced by Cashier, so the
     * plan is derived from the user's active subscription's price.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $this->planSync->fromUserActiveSubscription($payload['data']['object']['customer'] ?? '');

        return $this->successMethod();
    }

    /**
     * customer.subscription.updated — status/price change. Sync via Cashier,
     * then update the account plan from the event's status + price.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): ?Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->planSync->fromSubscriptionObject($payload['data']['object'] ?? []);

        return $response ?? $this->successMethod();
    }

    /**
     * customer.subscription.deleted — cancellation. Cashier marks the
     * subscription canceled; the account drops back to Free.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $this->planSync->downgradeToFree($payload['data']['object']['customer'] ?? '');

        return $response;
    }

    /**
     * invoice.paid — recurring payment succeeded; keep the plan active.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaid(array $payload): Response
    {
        $this->planSync->fromUserActiveSubscription($payload['data']['object']['customer'] ?? '');

        return $this->successMethod();
    }

    /**
     * invoice.payment_failed — payment failed. The plan is left to the
     * subscription status (handled by customer.subscription.updated); this only
     * logs the failure for operators.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        Log::warning('Stripe invoice payment failed.', [
            'stripe_customer_id' => $payload['data']['object']['customer'] ?? null,
            'subscription' => $payload['data']['object']['subscription'] ?? null,
        ]);

        return $this->successMethod();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : null;
    }

    private function alreadyProcessed(string $eventId): bool
    {
        return StripeWebhookEvent::query()
            ->where('stripe_event_id', $eventId)
            ->exists();
    }

    private function markProcessed(string $eventId, ?string $type): void
    {
        StripeWebhookEvent::firstOrCreate(
            ['stripe_event_id' => $eventId],
            ['type' => $type],
        );
    }
}
