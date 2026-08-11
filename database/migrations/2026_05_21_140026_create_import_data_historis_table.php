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
        Schema::create('import_data_historis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->enum('tipe_import', ['lplpo', 'rko', 'pemakaian']);
            $table->enum('status', ['pending', 'proses', 'selesai', 'gagal'])->default('pending');
            $table->integer('total_baris');
            $table->integer('baris_berhasil');
            $table->integer('baris_gagal');
            $table->text('pesan_error')->nullable();
            $table->foreignId('diimport_oleh')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_import');
            $table->timestamps();
            $table->index(['tipe_import', 'status', 'tanggal_import']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_data_historis');
    }
};
