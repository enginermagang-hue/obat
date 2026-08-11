<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus user_id dari fasilitas_kesehatan
        Schema::table('fasilitas_kesehatan', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Tambah fasilitas_kesehatan_id ke users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('fasilitas_kesehatan_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('fasilitas_kesehatan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Kembalikan user_id ke fasilitas_kesehatan
        Schema::table('fasilitas_kesehatan', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Hapus fasilitas_kesehatan_id dari users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['fasilitas_kesehatan_id']);
            $table->dropColumn('fasilitas_kesehatan_id');
        });
    }
};
