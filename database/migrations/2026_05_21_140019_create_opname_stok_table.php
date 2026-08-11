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
        Schema::create('opname_stok', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_opname')->unique();
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->date('tanggal_opname');
            $table->enum('status', ['draft', 'proses', 'selesai'])->default('draft');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['fasilitas_id', 'tanggal_opname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opname_stok');
    }
};
