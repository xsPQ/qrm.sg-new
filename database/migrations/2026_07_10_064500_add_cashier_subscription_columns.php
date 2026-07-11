<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Cashier v16 subscription columns (P2-T10 / [DEV-162](/DEV/issues/DEV-162)).
 *
 * Cashier v16 stores the subscription "name/type" bucket in `subscriptions.type`
 * and meters on `subscription_items`. The original P1 migrations followed the
 * Pflichtenheft schema (§6.2), which predates these Cashier v16 columns. The
 * Stripe webhook is the first code path that creates/updates local subscription
 * rows via Cashier, so this migration is the prerequisite that unblocks it.
 *
 * App\\Models\\Subscription (Filament admin, read-only) already declares `type`
 * as fillable, so this column is consumed by both Cashier and the admin model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Cashier v16 subscription "name/type" bucket (e.g. "default").
            $table->string('type')->default('default')->after('user_id')->index();
        });

        Schema::table('subscription_items', function (Blueprint $table) {
            // Optional Cashier v16 metered-billing columns (nullable; unused for
            // the simple Pro/Business prices but required by Cashier's item sync).
            $table->string('meter_id')->nullable()->after('stripe_price');
            $table->string('meter_event_name')->nullable()->after('meter_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropColumn(['meter_event_name', 'meter_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
