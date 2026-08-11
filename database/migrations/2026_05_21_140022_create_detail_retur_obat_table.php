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
        Schema::create('detail_retur_obat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('retur_obat')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('obat')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batch_stok')->nullOnDelete();
            $table->integer('jumlah_retur');
            $table->string('bukti_foto')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['retur_id', 'obat_id', 'batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_retur_obat');
    }
};
