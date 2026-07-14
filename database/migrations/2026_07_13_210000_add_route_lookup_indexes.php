<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_code_routes')) {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_route_code ON qr_code_routes (code)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_route_alias ON qr_code_routes (alias)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_route_code');
        DB::statement('DROP INDEX IF EXISTS idx_route_alias');
    }
};
