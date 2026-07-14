<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('title');
            $table->text('type');
            $table->json('content');
            $table->json('settings')->nullable();
            $table->text('status')->default('active');
            $table->json('entitlement_snapshot')->nullable();
            $table->text('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('burn')->default(false);
            $table->unsignedInteger('max_scans')->nullable();
            $table->unsignedInteger('scan_count')->default(0);
            $table->timestamp('next_cleanup_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_qr_user');
            $table->index('type', 'idx_qr_type');
            $table->index('status', 'idx_qr_status');
            $table->index('expires_at', 'idx_qr_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
