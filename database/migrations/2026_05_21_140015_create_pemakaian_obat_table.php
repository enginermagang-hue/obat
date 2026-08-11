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
        Schema::create('pemakaian_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batch_stok')->nullOnDelete();
            $table->date('tanggal_pemakaian');
            $table->enum('jenis_pelayanan', ['rawat_jalan', 'rawat_inap', 'uks', 'posyandu', 'pusling', 'gigi', 'laboratorium', 'apotek', 'lainnya']);
            $table->integer('jumlah');
            $table->integer('jumlah_pasien')->nullable();
            $table->string('diagnosa_kode')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['fasilitas_id', 'obat_id', 'tanggal_pemakaian', 'jenis_pelayanan', 'batch_id'], 'idx_pemakaian_faskes_obat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemakaian_obat');
    }
};
