<?php

declare(strict_types=1);

/*
 * qrm.sg billing configuration (Pflichtenheft §3.5.1–§3.5.2, P2-T09).
 *
 * Maps the checkout-eligible plans (Pro, Business) to their Stripe price IDs
 * and centralises the Checkout / Customer Portal return URLs. Free is never a
 * checkout target — there is no payment for it.
 *
 * Stripe itself stays the single source of truth for paid features: the
 * successful webhook (P2-T10 / [DEV-162](/DEV/issues/DEV-162)) finalises every
 * plan change. This config only describes *which* Stripe price a Checkout
 * Session is created for; it never asserts a user's paid plan locally.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Checkout-eligible plans
    |--------------------------------------------------------------------------
    |
    | Each entry maps a plan key (matching App\Enums\UserPlan) to its Stripe
    | price ID. The keys also drive CheckoutRequest validation, so the set of
    | keys here must stay in sync with the request rules.
    |
    */

    'plans' => [
        'pro' => [
            'price_id' => env('STRIPE_PRICE_PRO'),
            'label' => 'Pro',
        ],
        'business' => [
            'price_id' => env('STRIPE_PRICE_BUSINESS'),
            'label' => 'Business',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Premium Alias (One-Time Purchase)
    |--------------------------------------------------------------------------
    |
    | Aliases with ≤4 characters are premium shortcodes. Any user (including
    | Free) can buy one for a one-time fee. After purchase, the alias is
    | permanently assigned to the user's account and can be used for any
    | QR code.
    |
    */

    'premium_alias' => [
        'price_id' => env('STRIPE_PRICE_PREMIUM_ALIAS'),
        'cost' => 1.00,            // EUR, one-time
        'max_length' => 4,         // aliases with ≤4 chars are premium
        'currency' => 'eur',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription type
    |--------------------------------------------------------------------------
    |
    | Cashier subscription "name/type" bucket used for upgrades. Cashier v16
    | stores this in the `subscriptions.type` column.
    |
    */

    'subscription_type' => env('CASHIER_SUBSCRIPTION_TYPE', 'default'),

    'checkout' => [
        // Absolute return URLs passed to the Stripe Checkout Session. Fall back
        // to the dashboard route when unset or empty (see CreateCheckoutSession).
        'success_url' => env('BILLING_CHECKOUT_SUCCESS_URL') ?: null,
        'cancel_url' => env('BILLING_CHECKOUT_CANCEL_URL') ?: null,

        // Whether Stripe Checkout accepts promotion/redeem codes.
        'allow_promotion_codes' => env('BILLING_ALLOW_PROMOTION_CODES', true),
    ],

    'portal' => [
        // Absolute return URL for the Stripe Customer Portal. Falls back to the
        // dashboard route when unset or empty (see CreateBillingPortalSession).
        'return_url' => env('BILLING_PORTAL_RETURN_URL') ?: null,
    ],

];
