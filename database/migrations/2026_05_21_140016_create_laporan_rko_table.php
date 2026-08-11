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
        Schema::create('laporan_rko', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_rko')->unique();
            $table->foreignId('fasilitas_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->cascadeOnDelete();
            $table->integer('periode_tahun');
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('draft');
            $table->date('tanggal_pembuatan');
            $table->date('tanggal_pengajuan')->nullable();
            $table->date('tanggal_disetujui')->nullable();
            $table->decimal('total_anggaran', 14, 2)->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['fasilitas_id', 'periode_tahun', 'sumber_dana_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_rko');
    }
};
