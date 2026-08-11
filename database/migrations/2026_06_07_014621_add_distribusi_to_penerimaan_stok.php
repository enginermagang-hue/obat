<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah nilai enum 'distribusi' dan 'manual' ke kolom tipe di tabel penerimaan_stok.
     * Tambah kolom distribusi_id (nullable FK ke distribusi_obat) di tabel penerimaan_stok.
     * Tambah kolom penerimaan_stok_id (nullable FK ke penerimaan_stok) di tabel distribusi_obat.
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE penerimaan_stok MODIFY COLUMN tipe ENUM('pembelian', 'hibah', 'stok_awal', 'penyesuaian', 'distribusi', 'manual') NOT NULL");
        }

        Schema::table('penerimaan_stok', function (Blueprint $table) {
            $table->foreignId('distribusi_id')
                ->nullable()
                ->after('sumber_dana_id')
                ->constrained('distribusi_obat')
                ->nullOnDelete();
        });

        Schema::table('distribusi_obat', function (Blueprint $table) {
            $table->foreignId('penerimaan_stok_id')
                ->nullable()
                ->after('penerima_id')
                ->constrained('penerimaan_stok')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribusi_obat', function (Blueprint $table) {
            $table->dropForeign(['penerimaan_stok_id']);
            $table->dropColumn('penerimaan_stok_id');
        });

        Schema::table('penerimaan_stok', function (Blueprint $table) {
            $table->dropForeign(['distribusi_id']);
            $table->dropColumn('distribusi_id');
        });

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE penerimaan_stok MODIFY COLUMN tipe ENUM('pembelian', 'hibah', 'stok_awal', 'penyesuaian') NOT NULL");
        }
    }
};
