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
        Schema::create('batch_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('tanggal_expired');
            $table->integer('jumlah')->default(0);
            $table->enum('status', ['tersedia', 'karantina', 'expired', 'dimusnahkan'])->default('tersedia');
            $table->date('tanggal_masuk');
            $table->string('supplier')->nullable();
            $table->decimal('harga_beli', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['obat_id', 'fasilitas_id', 'tanggal_expired', 'status', 'batch_number'], 'idx_batch_faskes_expired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_stok');
    }
};
