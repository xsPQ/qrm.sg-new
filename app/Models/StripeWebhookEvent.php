<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency record for Stripe webhook events (P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * One row per processed Stripe event id. The unique index on
 * {@see $stripeEventId} guarantees that a duplicate delivery of the same event
 * never re-runs its handler.
 */
class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'type',
    ];
}
