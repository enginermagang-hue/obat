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
        Schema::create('penerimaan_stok', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penerimaan')->unique();
            $table->enum('tipe', ['pembelian', 'hibah', 'stok_awal', 'penyesuaian']);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('nomor_po')->nullable();
            $table->string('nomor_invoice')->nullable();
            $table->date('tanggal_penerimaan');
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'dikonfirmasi', 'dibatalkan'])->default('draft');
            $table->text('catatan')->nullable();
            $table->decimal('total_biaya', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['tipe', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_stok');
    }
};
