<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_stats_hourly', function (Blueprint $table) {
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->timestamp('hour');
            $table->unsignedInteger('scan_count')->default(0);
            $table->unsignedInteger('unique_ips')->default(0);

            $table->primary(['qr_code_id', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_stats_hourly');
    }
};
