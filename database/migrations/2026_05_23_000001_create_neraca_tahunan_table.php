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
        Schema::create('neraca_tahunan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_neraca', 100)->unique();
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->integer('tahun');
            $table->enum('status', ['draft', 'selesai'])->default('draft');
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['fasilitas_id', 'tahun']);
        });

        Schema::create('detail_neraca_tahunan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neraca_id')->constrained('neraca_tahunan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->integer('stok_awal')->default(0);
            $table->integer('total_masuk')->default(0);
            $table->integer('total_keluar')->default(0);
            $table->integer('stok_akhir')->default(0);
            $table->decimal('harga_satuan', 12, 2)->nullable();
            $table->decimal('nilai_stok', 14, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['neraca_id', 'obat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_neraca_tahunan');
        Schema::dropIfExists('neraca_tahunan');
    }
};
