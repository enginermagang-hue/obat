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
        Schema::create('permintaan_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_permintaan')->unique();
            $table->foreignId('fasilitas_pengirim_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->foreignId('fasilitas_tujuan_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->enum('tipe_permintaan', ['pustu_ke_puskesmas', 'puskesmas_ke_dinas']);
            $table->foreignId('lplpo_id')->nullable()->constrained('laporan_lplpo')->nullOnDelete();
            $table->enum('status', ['draft', 'menunggu_persetujuan', 'disetujui', 'ditolak', 'sedang_didistribusi', 'diterima', 'dibatalkan'])->default('draft');
            $table->date('tanggal_permintaan');
            $table->date('tanggal_disetujui')->nullable();
            $table->date('tanggal_ditolak')->nullable();
            $table->date('tanggal_dikirim')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['status', 'tipe_permintaan', 'tanggal_permintaan', 'lplpo_id'], 'idx_permintaan_status_tipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_obat');
    }
};
