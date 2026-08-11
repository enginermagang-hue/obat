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
        Schema::create('obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat')->unique();
            $table->string('nama_obat');
            $table->string('nama_generik')->nullable();
            $table->string('kategori');
            $table->string('satuan');
            $table->string('kekuatan')->nullable();
            $table->enum('bentuk_sediaan', ['tablet', 'kapsul', 'sirup', 'salep', 'injeksi', 'drop', 'inhaler', 'suppositoria']);
            $table->string('produsen')->nullable();
            $table->string('kemasan')->nullable();
            $table->decimal('harga_satuan', 12, 2)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->index(['kategori', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obat');
    }
};
