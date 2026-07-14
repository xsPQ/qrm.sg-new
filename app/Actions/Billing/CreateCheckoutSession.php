<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Domain\Billing\Plan;
use App\Domain\Billing\PlanNotConfiguredException;
use App\Models\User;
use InvalidArgumentException;
use Laravel\Cashier\Checkout;

/**
 * Create a Stripe Checkout Session for a plan upgrade (Pflichtenheft §3.5.2, P2-T09).
 *
 * Resolves the requested plan's Stripe price, ensures the user has a Stripe
 * customer (persisting <code>stripe_id</code> on the user the first time) and
 * builds a subscription Checkout Session with the configured success/cancel
 * return URLs.
 *
 * This action deliberately does **not** mark the user as paid. Stripe Checkout's
 * browser redirect is not proof of payment — only the validated webhook
 * ([DEV-162](/DEV/issues/DEV-162), P2-T10) may update the account plan. Cashier
 * persists the local subscription record when that webhook arrives.
 */
final class CreateCheckoutSession
{
    /**
     * @return Checkout The created Cashier Checkout session (exposes
     *                  <code>->url</code> and <code>->id</code>).
     *
     * @throws InvalidArgumentException When the plan is not a Checkout target.
     * @throws PlanNotConfiguredException When the plan has no price configured.
     */
    public function handle(User $user, string $plan): Checkout
    {
        $priceId = Plan::priceIdFor($plan);

        return $user->newSubscription($this->subscriptionType(), $priceId)
            ->checkout([
                'success_url' => $this->successUrl($plan),
                'cancel_url' => $this->cancelUrl(),
                'allow_promotion_codes' => (bool) config('billing.checkout.allow_promotion_codes', true),
            ]);
    }

    private function subscriptionType(): string
    {
        return (string) config('billing.subscription_type', 'default');
    }

    /**
     * Success return URL. Includes the chosen plan so the dashboard can render
     * a "pending confirmation" hint until the webhook finalises the change.
     */
    private function successUrl(string $plan): string
    {
        $configured = config('billing.checkout.success_url');

        return is_string($configured) && $configured !== ''
            ? $configured
            : route('dashboard').'?checkout=success&plan='.urlencode($plan);
    }

    private function cancelUrl(): string
    {
        $configured = config('billing.checkout.cancel_url');

        return is_string($configured) && $configured !== ''
            ? $configured
            : route('dashboard').'?checkout=cancelled';
    }
}
