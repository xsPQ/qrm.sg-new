<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PremiumAliasPurchase;
use App\Services\QrCodeRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

/**
 * Premium alias purchase via Stripe Checkout (one-time payment).
 *
 * Flow:
 * 1. User enters an alias ≤4 chars in the Creator/Editor.
 * 2. UI shows "Premium alias — unlock for 1.00 €" with a buy button.
 * 3. POST /api/billing/premium-alias/checkout { alias: "abc" }
 * 4. Backend creates a Stripe Checkout Session (one-time, 1.00 €).
 * 5. After payment: Stripe webhook marks the purchase as 'paid'.
 * 6. User can now use the alias on any QR code.
 */
class PremiumAliasCheckoutController extends Controller
{
    public function __construct(
        private readonly QrCodeRouteService $routeService,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alias' => ['required', 'string', 'min:1', 'max:4', 'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?$/'],
        ]);

        $alias = strtolower($validated['alias']);
        $user = $request->user();

        // Already owned by this user?
        if (PremiumAliasPurchase::isAliasOwnedBy($alias, $user->id)) {
            return response()->json([
                'message' => 'You already own this alias.',
                'status' => 'already_owned',
            ]);
        }

        // Already purchased by someone else?
        if (PremiumAliasPurchase::isAliasPurchased($alias)) {
            return response()->json([
                'message' => 'This alias is already taken.',
                'status' => 'taken',
            ], 409);
        }

        // Check if alias collides with existing route
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        if (! $this->routeService->isAliasAvailable($alias, $host)) {
            return response()->json([
                'message' => 'This alias is already in use.',
                'status' => 'taken',
            ], 409);
        }

        $priceId = config('billing.premium_alias.price_id');
        if (! $priceId) {
            return response()->json([
                'message' => 'Premium alias purchases are not configured.',
            ], 503);
        }

        // Create pending purchase record
        $purchase = PremiumAliasPurchase::create([
            'user_id' => $user->id,
            'alias' => $alias,
            'status' => 'pending',
        ]);

        try {
            Stripe::setApiKey(config('cashier.secret') ?: env('STRIPE_SECRET'));

            $session = StripeSession::create([
                'mode' => 'payment',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'type' => 'premium_alias',
                    'alias' => $alias,
                    'user_id' => (string) $user->id,
                    'purchase_id' => (string) $purchase->id,
                ],
                'client_reference_id' => (string) $purchase->id,
                'success_url' => route('billing.premium-alias.success', ['purchase' => $purchase->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.premium-alias.cancel', ['purchase' => $purchase->id]),
            ]);

            $purchase->update([
                'stripe_checkout_session_id' => $session->id,
            ]);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
                'purchase_id' => $purchase->id,
                'alias' => $alias,
            ]);
        } catch (\Exception $e) {
            $purchase->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Could not start checkout. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Success redirect from Stripe Checkout. The actual payment confirmation
     * happens via webhook. This page just confirms the purchase is processing.
     */
    public function success(Request $request, int $purchase): JsonResponse
    {
        $purchase = PremiumAliasPurchase::findOrFail($purchase);

        if ($purchase->user_id !== $request->user()->id) {
            abort(403);
        }

        // Check Stripe session status
        if ($purchase->stripe_checkout_session_id && $purchase->status === 'pending') {
            try {
                Stripe::setApiKey(config('cashier.secret') ?: env('STRIPE_SECRET'));
                $session = StripeSession::retrieve($purchase->stripe_checkout_session_id);

                if ($session->payment_status === 'paid') {
                    $purchase->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'stripe_payment_intent_id' => $session->payment_intent,
                    ]);
                }
            } catch (\Exception) {
                // Webhook will handle it
            }
        }

        return response()->json([
            'status' => $purchase->status,
            'alias' => $purchase->alias,
            'message' => $purchase->isPaid()
                ? "Alias '{$purchase->alias}' is now yours!"
                : 'Payment is being processed. Your alias will be activated shortly.',
        ]);
    }

    /**
     * Cancel redirect from Stripe Checkout.
     */
    public function cancel(Request $request, int $purchase): JsonResponse
    {
        $purchaseModel = PremiumAliasPurchase::findOrFail($purchase);

        if ($purchaseModel->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($purchaseModel->status === 'pending') {
            $purchaseModel->delete();
        }

        return response()->json([
            'status' => 'cancelled',
            'message' => 'Purchase cancelled.',
        ]);
    }
}
