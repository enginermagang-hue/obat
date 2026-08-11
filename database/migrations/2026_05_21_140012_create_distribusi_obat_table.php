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
        Schema::create('distribusi_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat_jalan')->unique();
            $table->foreignId('permintaan_id')->constrained('permintaan_obat')->cascadeOnDelete();
            $table->enum('tipe_distribusi', ['dinas_ke_puskesmas', 'puskesmas_ke_pustu']);
            $table->foreignId('fasilitas_pengirim_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->foreignId('fasilitas_penerima_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->enum('status', ['draft', 'dalam_pengiriman', 'diterima', 'ditolak'])->default('draft');
            $table->date('tanggal_kirim')->nullable();
            $table->date('tanggal_terima')->nullable();
            $table->foreignId('pengirim_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('penerima_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['permintaan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi_obat');
    }
};
