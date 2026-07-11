<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Enums\UserPlan;
use InvalidArgumentException;

/**
 * Checkout-eligible plan resolution (Pflichtenheft §3.5.2, P2-T09).
 *
 * Only paid plans can be the target of a Stripe Checkout Session. {@see UserPlan::Free}
 * is never a checkout target — there is no payment for it. The two upgrade
 * targets ({@see UserPlan::Pro}, {@see UserPlan::Business}) resolve to their
 * Stripe price IDs configured in <code>config/billing.php</code>.
 *
 * This class is pure: it never talks to Stripe and never mutates the user's
 * plan — it only validates the requested checkout target and resolves the price.
 */
final class Plan
{
    /** Plans a user may upgrade to via Stripe Checkout. */
    public const CHECKOUT_TARGETS = [UserPlan::Pro->value, UserPlan::Business->value];

    /**
     * Whether the given plan key is a valid Checkout target.
     */
    public static function isCheckoutTarget(string $plan): bool
    {
        return in_array(strtolower($plan), self::CHECKOUT_TARGETS, true);
    }

    /**
     * Resolve the Stripe price ID for a Checkout target plan.
     *
     * @param  string  $plan  Plan key (e.g. "pro", "business")
     * @return string The Stripe price ID (e.g. "price_…")
     *
     * @throws InvalidArgumentException When the plan is not a Checkout target
     *                                  (e.g. "free" or an unknown value).
     * @throws PlanNotConfiguredException When the plan is a valid target but
     *                                    no Stripe price ID is configured.
     */
    public static function priceIdFor(string $plan): string
    {
        $plan = strtolower($plan);

        if (! self::isCheckoutTarget($plan)) {
            throw new InvalidArgumentException("Plan [{$plan}] is not a checkout target.");
        }

        $priceId = config("billing.plans.{$plan}.price_id");

        if (! is_string($priceId) || $priceId === '') {
            throw new PlanNotConfiguredException($plan);
        }

        return $priceId;
    }

    /**
     * Reverse map: resolve the plan a Stripe price ID belongs to.
     *
     * Used by the Stripe webhook ([DEV-162](/DEV/issues/DEV-162)) to derive the
     * account plan from a subscription's price. Returns null for prices that are
     * not configured (e.g. a legacy/unknown price), in which case the caller
     * leaves the account on its current plan.
     */
    public static function planForPrice(string $priceId): ?UserPlan
    {
        foreach ((array) config('billing.plans') as $key => $config) {
            if (($config['price_id'] ?? null) === $priceId) {
                return UserPlan::tryFrom($key);
            }
        }

        return null;
    }
}
