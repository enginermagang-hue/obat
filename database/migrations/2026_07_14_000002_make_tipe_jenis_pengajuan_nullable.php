<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->string('tipe_pengajuan')->nullable()->default(null)->change();
            $table->string('jenis_pengajuan')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->enum('tipe_pengajuan', ['pustu_ke_puskesmas', 'puskesmas_ke_dinas'])->change();
            $table->enum('jenis_pengajuan', ['rutin', 'tambahan'])->default('rutin')->change();
        });
    }
};
