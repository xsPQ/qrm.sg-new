<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_stats_daily', function (Blueprint $table) {
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('scan_count')->default(0);
            $table->unsignedInteger('unique_ips')->default(0);
            $table->text('top_country')->nullable();
            $table->text('top_device')->nullable();

            $table->primary(['qr_code_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_stats_daily');
    }
};
