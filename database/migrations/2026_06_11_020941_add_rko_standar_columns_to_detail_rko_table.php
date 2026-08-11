<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_rko', function (Blueprint $table) {
            $table->decimal('buffer_stock_persen', 5, 2)->default(0)->after('total_harga');
            $table->integer('buffer_stok_qty')->default(0)->after('buffer_stock_persen');
            $table->integer('rencana_kebutuhan')->default(0)->after('buffer_stok_qty');
            $table->char('abc_kategori', 1)->nullable()->after('rencana_kebutuhan');
        });
    }

    public function down(): void
    {
        Schema::table('detail_rko', function (Blueprint $table) {
            $table->dropColumn(['abc_kategori', 'rencana_kebutuhan', 'buffer_stok_qty', 'buffer_stock_persen']);
        });
    }
};
