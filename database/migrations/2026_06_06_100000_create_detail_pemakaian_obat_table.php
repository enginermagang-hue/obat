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
        Schema::create('detail_pemakaian_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemakaian_id')
                ->constrained('pemakaian_obat')
                ->cascadeOnDelete();
            $table->foreignId('obat_id')
                ->constrained('obat')
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batch_stok')
                ->nullOnDelete();
            $table->unsignedInteger('jumlah');
            $table->string('dosis', 100)->nullable();
            $table->string('satuan_dosis', 50)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['pemakaian_id', 'obat_id'], 'idx_detail_pemakaian_obat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pemakaian_obat');
    }
};
