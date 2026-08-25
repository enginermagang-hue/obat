<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izinkan baris breakdown tanpa sumber dana (item manual / tanpa riwayat
     * ber-SD) sehingga fallback agregat tetap bisa tampil di PDF & Excel.
     */
    public function up(): void
    {
        Schema::table('detail_neraca_sumber_dana', function (Blueprint $table): void {
            $table->foreignId('sumber_dana_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('detail_neraca_sumber_dana')
            ->whereNull('sumber_dana_id')
            ->delete();

        Schema::table('detail_neraca_sumber_dana', function (Blueprint $table): void {
            $table->foreignId('sumber_dana_id')->change();
        });
    }
};
