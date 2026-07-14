<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P3-T02 (DEV-623): extend scan_stats_daily with the most-frequent OS and
     * browser per QR-code/day, complementing top_country/top_device already
     * present (Pflichtenheft §3.3.2, §6.2). The daily roll-up scheduled job
     * fills these from the os_family/browser_family columns of the scans table.
     */
    public function up(): void
    {
        Schema::table('scan_stats_daily', function (Blueprint $table) {
            $table->text('top_os')->nullable()->after('top_device');
            $table->text('top_browser')->nullable()->after('top_os');
        });
    }

    public function down(): void
    {
        Schema::table('scan_stats_daily', function (Blueprint $table) {
            $table->dropColumn(['top_os', 'top_browser']);
        });
    }
};
