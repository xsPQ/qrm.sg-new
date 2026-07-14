<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use App\Http\Controllers\Billing\BillingCheckoutController;
use RuntimeException;

/**
 * Thrown when a Checkout target plan (Pro/Business) has no Stripe price ID
 * configured in <code>config/billing.php</code> / the matching environment
 * variable.
 *
 * This is a server-configuration error, not a client error: the requested plan
 * is valid, but the deployment has not wired it to a Stripe price. Mapped to a
 * 503 by {@see BillingCheckoutController}.
 */
final class PlanNotConfiguredException extends RuntimeException
{
    public function __construct(string $plan)
    {
        parent::__construct("No Stripe price ID is configured for plan [{$plan}].");
    }
}
