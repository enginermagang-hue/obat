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
        Schema::create('fasilitas_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_faskes')->unique();
            $table->string('nama');
            $table->enum('tipe', ['puskesmas', 'pustu']);
            $table->foreignId('puskesmas_induk_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->text('alamat');
            $table->string('kecamatan');
            $table->string('kabupaten');
            $table->string('telepon')->nullable();
            $table->string('kepala_faskes')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->index(['tipe', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fasilitas_kesehatan');
    }
};
