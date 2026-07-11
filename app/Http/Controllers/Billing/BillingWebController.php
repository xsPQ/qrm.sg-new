<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreateBillingPortalSession;
use App\Actions\Billing\CreateCheckoutSession;
use App\Domain\Billing\Plan;
use App\Domain\Billing\PlanNotConfiguredException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\InvalidCustomer;

/**
 * Browser-facing billing entry points for the Account page
 * (Pflichtenheft §3.5.1–§3.5.2, P2-T05 / [DEV-157](/DEV/issues/DEV-157)).
 *
 * Thin web glue that delegates to the P2-T09 ([DEV-161](/DEV/issues/DEV-161))
 * actions and redirects the browser to the Stripe URL they return. These routes
 * exist so the server-rendered Account page can link to Stripe Checkout / the
 * Customer Portal with a normal CSRF-protected POST form; they contain no
 * billing logic of their own. Only the validated Stripe webhook
 * ([DEV-162](/DEV/issues/DEV-162)) may change the account plan.
 */
class BillingWebController extends Controller
{
    public function __construct(
        private readonly CreateCheckoutSession $checkout,
        private readonly CreateBillingPortalSession $portal,
    ) {}

    /**
     * Start a Stripe Checkout Session for an upgrade and redirect to it.
     *
     * Rejects non-checkout-target plans (Free / unknown) up front. When Stripe
     * is not configured (no price id), bounces the user back to the Account
     * page with an error instead of crashing.
     */
    public function checkout(Request $request, string $plan): RedirectResponse
    {
        if (! Plan::isCheckoutTarget($plan)) {
            return back()
                ->with('billing_error', __('This plan is not available for checkout.'));
        }

        try {
            $session = $this->checkout->handle($request->user(), $plan);
        } catch (PlanNotConfiguredException $e) {
            return back()
                ->with('billing_error', __('Checkout for this plan is not configured.'));
        }

        return redirect()->away((string) $session->url);
    }

    /**
     * Open the Stripe Customer Portal to manage an existing subscription.
     *
     * Requires an existing Stripe customer (i.e. a Checkout Session must have
     * been started first); otherwise bounces back to the Account page.
     */
    public function portal(Request $request): RedirectResponse
    {
        try {
            $url = $this->portal->handle($request->user());
        } catch (InvalidCustomer $e) {
            return back()
                ->with('billing_error', __('No billing account found. Start a subscription first.'));
        }

        return redirect()->away($url);
    }
}
