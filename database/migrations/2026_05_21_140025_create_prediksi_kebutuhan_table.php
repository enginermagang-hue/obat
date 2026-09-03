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
        Schema::create('prediksi_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('model_prediksi')->nullOnDelete();
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->integer('jumlah_prediksi');
            $table->integer('confidence_lower')->nullable();
            $table->integer('confidence_upper')->nullable();
            $table->enum('metode', ['ai_gradient_boost', 'ai_random_forest', 'moving_average', 'manual', 'ann_php']);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['fasilitas_id', 'obat_id', 'periode_bulan', 'periode_tahun'], 'uq_prediksi_faskes_obat_periode');
            $table->index('metode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediksi_kebutuhan');
    }
};
