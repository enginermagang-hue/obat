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
        Schema::create('sumber_dana_penggunaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->cascadeOnDelete();
            $table->foreignId('rko_id')->nullable()->constrained('laporan_rko')->nullOnDelete();
            $table->foreignId('fasilitas_id')->constrained('fasilitas_kesehatan')->cascadeOnDelete();
            $table->enum('tipe', ['perencanaan', 'realisasi']);
            $table->integer('jumlah_obat');
            $table->decimal('total_biaya', 14, 2);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['sumber_dana_id', 'tipe', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sumber_dana_penggunaan');
    }
};
