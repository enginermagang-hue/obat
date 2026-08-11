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
        Schema::create('retur_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur')->unique();
            $table->foreignId('distribusi_id')->nullable()->constrained('distribusi_obat')->nullOnDelete();
            $table->foreignId('fasilitas_pengirim_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->foreignId('fasilitas_penerima_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->enum('tipe_retur', ['puskesmas_ke_gudang', 'pustu_ke_puskesmas', 'gudang_ke_supplier']);
            $table->enum('alasan', ['expired', 'rusak', 'kelebihan_stok', 'salah_kirim', 'recall', 'near_expiry', 'lainnya']);
            $table->text('alasan_lainnya')->nullable();
            $table->enum('status', ['draft', 'menunggu_approval', 'disetujui', 'ditolak', 'dalam_pengiriman', 'diterima', 'selesai'])->default('draft');
            $table->date('tanggal_retur');
            $table->date('tanggal_disetujui')->nullable();
            $table->date('tanggal_ditolak')->nullable();
            $table->date('tanggal_dikirim')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['status', 'tipe_retur', 'alasan', 'tanggal_retur']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_obat');
    }
};
