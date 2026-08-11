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
        Schema::create('inspeksi_retur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('retur_obat')->cascadeOnDelete();
            $table->foreignId('detail_retur_id')->constrained('detail_retur_obat')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batch_stok')->cascadeOnDelete();
            $table->enum('hasil_inspeksi', ['layak', 'tidak_layak', 'perlu_tindakan_lanjut']);
            $table->enum('tindakan', ['didistribusi_kembali', 'dimusnahkan', 'dikembalikan_ke_supplier']);
            $table->text('catatan_inspeksi')->nullable();
            $table->foreignId('inspected_by')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_inspeksi');
            $table->timestamps();
            $table->index(['retur_id', 'detail_retur_id', 'hasil_inspeksi', 'tindakan'], 'idx_inspeksi_retur_hasil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspeksi_retur');
    }
};
