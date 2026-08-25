<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('opname_stok', function (Blueprint $table) {
                $table->enum('tipe', ['penyesuaian', 'stok_baru'])->default('penyesuaian')->after('nomor_opname');
            });
        }

        Schema::table('detail_opname_stok', function (Blueprint $table) {
            $table->string('batch_number', 100)->nullable()->after('selisih');
            $table->date('tanggal_expired')->nullable()->after('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('opname_stok', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });

        Schema::table('detail_opname_stok', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'tanggal_expired']);
        });
    }
};
