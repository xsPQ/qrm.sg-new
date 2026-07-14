<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->jsonb('snapshot');
            $table->string('change_summary')->nullable();
            $table->timestamps();

            $table->index(['qr_code_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_revisions');
    }
};
