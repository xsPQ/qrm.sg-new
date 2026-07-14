<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreateBillingPortalSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\InvalidCustomer;

/**
 * Open the Stripe Customer Portal (Pflichtenheft §3.5.1, P2-T09).
 *
 * <code>POST /api/billing/portal</code> — authenticated. Returns the Stripe
 * Customer Portal URL so the dashboard can redirect the browser there to manage
 * the subscription (change plan, update payment, cancel). Requires an existing
 * Stripe customer, i.e. a Checkout Session must have been started first.
 */
class BillingPortalController extends Controller
{
    public function __construct(private readonly CreateBillingPortalSession $portal) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $url = $this->portal->handle($request->user());
        } catch (InvalidCustomer $e) {
            return response()->json([
                'message' => 'No billing account found. Start a subscription first.',
            ], 404);
        }

        return response()->json([
            'url' => $url,
        ]);
    }
}
