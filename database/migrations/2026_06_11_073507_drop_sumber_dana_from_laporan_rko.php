<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_rko', function (Blueprint $table) {
            $table->dropForeign(['sumber_dana_id']);
            $table->unique(['fasilitas_id', 'periode_tahun']);
            $table->dropUnique(['fasilitas_id', 'periode_tahun', 'sumber_dana_id']);
            $table->dropColumn('sumber_dana_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_rko', function (Blueprint $table) {
            $table->foreignId('sumber_dana_id')->nullable()->after('fasilitas_id')->constrained('sumber_dana')->cascadeOnDelete();
            $table->dropUnique(['fasilitas_id', 'periode_tahun']);
            $table->unique(['fasilitas_id', 'periode_tahun', 'sumber_dana_id']);
        });
    }
};
