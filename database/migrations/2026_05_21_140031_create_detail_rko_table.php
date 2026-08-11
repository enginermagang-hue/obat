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
        Schema::create('detail_rko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rko_id')->constrained('laporan_rko')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->integer('pemakaian_tahun_sebelumnya');
            $table->integer('rata_rata_pemakaian_bulanan');
            $table->integer('stok_akhir');
            $table->integer('kebutuhan_tahunan');
            $table->integer('usulan');
            $table->decimal('harga_perkiraan', 12, 2)->nullable();
            $table->decimal('total_harga', 14, 2)->nullable();
            $table->foreignId('lplpo_referensi_id')->nullable()->constrained('laporan_lplpo')->nullOnDelete();
            $table->foreignId('prediksi_id')->nullable()->constrained('prediksi_kebutuhan')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_rko');
    }
};
