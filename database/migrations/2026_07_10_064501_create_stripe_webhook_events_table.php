<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Idempotent Stripe webhook processing (P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * Records every Stripe event id that has been processed so that Stripe's own
 * retries/duplicate deliveries never mutate state twice. The Stripe-Signature
 * check (replay/tolerance) is handled separately by Cashier's
 * VerifyWebhookSignature middleware; this table provides at-least-once →
 * effectively-once dedup on the event id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();

            // Stripe event id, e.g. "evt_…" — globally unique at Stripe.
            $table->string('stripe_event_id')->unique();

            // Event type, e.g. "checkout.session.completed" (for diagnostics).
            $table->string('type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
