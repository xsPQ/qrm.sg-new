<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEAT-06: A/B Testing for QR codes.
 *
 * A QR code of type 'url' or 'redirect' can have multiple destination URLs
 * (variants A, B, optionally C). Each variant tracks its own scan count and
 * can be targeted by device type or selected randomly with weighted
 * distribution. Pro/Business only feature (entitlement gated).
 *
 * The distribution strategy ('random' or 'device') and optional configuration
 * are stored on the QR code's settings JSON (settings.ab_testing) rather than
 * a dedicated column, keeping the table schema lean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->string('label', 10);       // 'A', 'B', 'C'
            $table->text('url');               // destination URL
            $table->unsignedSmallInteger('weight')->default(1); // for weighted random
            $table->unsignedInteger('scan_count')->default(0);
            $table->string('device_target', 20)->nullable(); // 'mobile', 'desktop', 'tablet', null=any
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('qr_code_id', 'idx_variants_qr');
            $table->unique(['qr_code_id', 'label'], 'uniq_variants_qr_label');
        });

        // Add variant_id to scans for per-variant analytics tracking.
        Schema::table('scans', function (Blueprint $table) {
            $table->foreignId('qr_code_variant_id')
                ->nullable()
                ->after('qr_code_id')
                ->constrained('qr_code_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['qr_code_variant_id']);
            $table->dropColumn('qr_code_variant_id');
        });

        Schema::dropIfExists('qr_code_variants');
    }
};
