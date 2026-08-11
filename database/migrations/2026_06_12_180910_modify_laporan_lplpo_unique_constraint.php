<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->index('fasilitas_id', 'lplpo_fasilitas_id_index');
        });

        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->dropUnique(['fasilitas_id', 'periode_bulan', 'periode_tahun']);
        });

        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->enum('jenis_pengajuan', ['rutin', 'tambahan'])->default('rutin')->after('tipe_pengajuan');
            $table->unique(['fasilitas_id', 'periode_bulan', 'periode_tahun', 'jenis_pengajuan'], 'lplpo_unique_per_jenis');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->dropUnique('lplpo_unique_per_jenis');
            $table->dropColumn('jenis_pengajuan');
            $table->unique(['fasilitas_id', 'periode_bulan', 'periode_tahun']);
        });

        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->dropIndex('lplpo_fasilitas_id_index');
        });
    }
};
