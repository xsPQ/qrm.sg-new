<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->text('ip_hash')->nullable();
            $table->text('user_agent_raw')->nullable();
            $table->json('user_agent_parsed')->nullable();
            $table->text('referer')->nullable();
            $table->text('accept_language')->nullable();
            $table->text('utm_source')->nullable();
            $table->text('utm_medium')->nullable();
            $table->text('utm_campaign')->nullable();
            $table->text('geo_country')->nullable();
            $table->text('geo_region')->nullable();
            $table->text('geo_city')->nullable();
            $table->text('geo_asn')->nullable();
            $table->text('device_type')->nullable();
            $table->text('os_family')->nullable();
            $table->text('browser_family')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->text('response_type');
            $table->unsignedSmallInteger('http_status');
            $table->unsignedInteger('response_time_ms');
            $table->timestamp('scanned_at');
            $table->timestamp('delete_after')->nullable();

            $table->index(['qr_code_id', 'scanned_at'], 'idx_scans_qr');
            $table->index('scanned_at', 'idx_scans_time');
            $table->index('delete_after', 'idx_scans_delete_after');
            $table->index('geo_country', 'idx_scans_country');
            $table->index('device_type', 'idx_scans_device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
