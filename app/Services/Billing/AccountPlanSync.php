<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Domain\Billing\Plan;
use App\Enums\UserPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

/**
 * Syncs the account-level plan from a Stripe subscription (P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * The Stripe webhook is the **single source of truth** for the paid account
 * plan. This service translates a Stripe subscription's status + price into the
 * {@see User::$plan} ("tier") field and persists it.
 *
 * Invariant (Pflichtenheft §3.5.3, P2-T08): this ONLY touches the account plan
 * field. It never reads or writes a QR-code's <code>entitlement_snapshot</code>
 * — grandfathered resources keep their creation-time snapshot regardless of
 * plan changes (Bestandsschutz).
 */
final class AccountPlanSync
{
    /**
     * Sync the plan from a Stripe subscription object payload.
     *
     * @param  array<string, mixed>  $subscription  The `data.object` of a
     *                                              `customer.subscription.*` event (contains `status`, `customer` and
     *                                              `items.data[].price.id`).
     */
    public function fromSubscriptionObject(array $subscription): void
    {
        $user = $this->userForCustomer($subscription['customer'] ?? null);

        if (! $user instanceof User) {
            return;
        }

        $price = $this->firstPrice($subscription);
        $plan = $this->resolvePlan($subscription['status'] ?? null, $price);

        $this->apply($user, $plan);
    }

    /**
     * Sync the plan from the user's currently-active Cashier subscription(s).
     *
     * Used by events whose payload has no price (e.g. `checkout.session.completed`,
     * `invoice.paid`): the subscription rows are synced first by Cashier, then
     * this reads the active subscription's price.
     */
    public function fromUserActiveSubscription(string $stripeCustomerId): void
    {
        $user = $this->userForCustomer($stripeCustomerId);

        if (! $user instanceof User) {
            return;
        }

        $this->apply($user, $this->activePlanForUser($user));
    }

    /**
     * Downgrade the account back to Free (e.g. on subscription deletion).
     */
    public function downgradeToFree(string $stripeCustomerId): void
    {
        $user = $this->userForCustomer($stripeCustomerId);

        if ($user instanceof User) {
            $this->apply($user, UserPlan::Free);
        }
    }

    private function apply(User $user, UserPlan $plan): void
    {
        if ($user->plan !== $plan->value) {
            $user->plan = $plan->value;
            $user->save();
        }
    }

    /**
     * Determine the plan from a Stripe subscription status and its price.
     *
     * Only statuses that grant access (active, trialing) map to a paid plan;
     * everything else (incomplete, past_due, canceled, unpaid, …) resolves to
     * Free so the account never stays elevated on a failing/expired subscription.
     */
    private function resolvePlan(?string $status, ?string $priceId): UserPlan
    {
        $granting = ['active', 'trialing'];

        if ($status !== null && in_array(strtolower($status), $granting, true) && $priceId !== null) {
            return Plan::planForPrice($priceId) ?? UserPlan::Free;
        }

        return UserPlan::Free;
    }

    private function activePlanForUser(User $user): UserPlan
    {
        /** @var Subscription|null $active */
        foreach ($this->cashierSubscriptions($user) as $subscription) {
            if ($subscription->active() && is_string($subscription->stripe_price) && $subscription->stripe_price !== '') {
                $mapped = Plan::planForPrice($subscription->stripe_price);

                if ($mapped instanceof UserPlan) {
                    return $mapped;
                }
            }
        }

        return UserPlan::Free;
    }

    /**
     * @return iterable<Subscription>
     */
    private function cashierSubscriptions(User $user): iterable
    {
        return $user->subscriptions()->get();
    }

    private function firstPrice(array $subscription): ?string
    {
        $price = $subscription['items']['data'][0]['price']['id'] ?? null;

        return is_string($price) && $price !== '' ? $price : null;
    }

    private function userForCustomer(mixed $stripeCustomerId): ?User
    {
        if (! is_string($stripeCustomerId) || $stripeCustomerId === '') {
            return null;
        }

        $billable = Cashier::findBillable($stripeCustomerId);

        if ($billable instanceof User) {
            return $billable;
        }

        Log::info('Stripe webhook referenced an unknown Stripe customer.', [
            'stripe_customer_id' => $stripeCustomerId,
        ]);

        return null;
    }
}
