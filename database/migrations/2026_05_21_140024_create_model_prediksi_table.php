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
        Schema::create('model_prediksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->longText('model_data');
            $table->decimal('akurasi_r2', 5, 4)->nullable();
            $table->date('tanggal_training');
            $table->integer('data_training_count');
            $table->json('fitur_digunakan')->nullable();
            $table->enum('status', ['aktif', 'kadaluarsa', 'gagal', 'data_belum_cukup'])->default('data_belum_cukup');
            $table->timestamps();
            $table->unique(['fasilitas_id', 'obat_id']);
            $table->index(['status', 'tanggal_training']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_prediksi');
    }
};
