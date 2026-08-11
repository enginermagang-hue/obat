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
        Schema::create('pengaturan_laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
            $table->enum('grup', ['kop_surat', 'tanda_tangan', 'identitas_laporan', 'default_laporan']);
            $table->string('key');
            $table->text('value');
            $table->timestamps();
            $table->unique(['fasilitas_id', 'grup', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_laporan');
    }
};
