<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreateCheckoutSession;
use App\Domain\Billing\PlanNotConfiguredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CheckoutRequest;
use Illuminate\Http\JsonResponse;

/**
 * Start a Stripe Checkout Session for a plan upgrade (Pflichtenheft §3.5.2, P2-T09).
 *
 * <code>POST /api/billing/checkout</code> — authenticated. Returns the Stripe
 * Checkout Session URL (and id) so the dashboard can redirect the browser to
 * Stripe. The actual plan change is finalised later by the validated webhook
 * ([DEV-162](/DEV/issues/DEV-162)), never by this redirect.
 */
class BillingCheckoutController extends Controller
{
    public function __construct(private readonly CreateCheckoutSession $checkout) {}

    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $plan = (string) $request->string('plan');

        try {
            $session = $this->checkout->handle($request->user(), $plan);
        } catch (PlanNotConfiguredException $e) {
            return response()->json([
                'message' => 'Checkout for this plan is not configured.',
            ], 503);
        }

        return response()->json([
            'url' => $session->url,
            'session_id' => $session->id,
            'plan' => $plan,
        ]);
    }
}
