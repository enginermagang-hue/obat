<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_neraca_tahunan', function (Blueprint $table) {
            $table->integer('stok_optimum')->nullable()->after('stok_akhir');
            $table->integer('permintaan')->nullable()->after('stok_optimum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_neraca_tahunan', function (Blueprint $table) {
            $table->dropColumn(['stok_optimum', 'permintaan']);
        });
    }
};
