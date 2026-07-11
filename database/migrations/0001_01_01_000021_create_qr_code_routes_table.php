<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->text('host')->default('qrm.sg');
            $table->text('code');
            $table->text('alias')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['host', 'code'], 'uniq_route_host_code');
            $table->unique(['host', 'alias'], 'uniq_route_host_alias');
            $table->index(['qr_code_id', 'host'], 'idx_route_qr_host');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_routes');
    }
};
