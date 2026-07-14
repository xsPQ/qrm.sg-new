<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use Laravel\Cashier\Exceptions\InvalidCustomer;

/**
 * Open the Stripe Customer Portal (Pflichtenheft §3.5.1, P2-T09).
 *
 * Returns the Stripe-hosted portal URL where the customer can manage their
 * subscription (change plan, update payment method, cancel). The portal is only
 * reachable once the user has a Stripe customer record (i.e. after a Checkout
 * Session has been started); otherwise Cashier's {@see InvalidCustomer} is
 * propagated to the controller, which maps it to a 404.
 */
final class CreateBillingPortalSession
{
    /**
     * @return string The Stripe Customer Portal session URL.
     *
     * @throws InvalidCustomer When the user has no Stripe customer yet.
     */
    public function handle(User $user): string
    {
        return $user->billingPortalUrl($this->returnUrl());
    }

    private function returnUrl(): string
    {
        $configured = config('billing.portal.return_url');

        return is_string($configured) && $configured !== ''
            ? $configured
            : route('dashboard');
    }
}
