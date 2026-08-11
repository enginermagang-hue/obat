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
        Schema::table('detail_rko', function (Blueprint $table) {
            $table->integer('total_kebutuhan')->default(0)->after('buffer_stok_qty');
            $table->string('ven_kategori', 1)->nullable()->after('total_kebutuhan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_rko', function (Blueprint $table) {
            $table->dropColumn(['total_kebutuhan', 'ven_kategori']);
        });
    }
};
