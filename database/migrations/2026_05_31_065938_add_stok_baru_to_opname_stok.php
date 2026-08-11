<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'stok_baru' to the tipe enum in opname_stok table.
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE opname_stok MODIFY COLUMN tipe ENUM('penyesuaian', 'stok_awal', 'stok_baru') DEFAULT 'penyesuaian'");
        }
    }

    /**
     * Remove 'stok_baru' from the tipe enum.
     */
    public function down(): void
    {
        DB::statement("UPDATE opname_stok SET tipe = 'stok_awal' WHERE tipe = 'stok_baru'");

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE opname_stok MODIFY COLUMN tipe ENUM('penyesuaian', 'stok_awal') DEFAULT 'penyesuaian'");
        }
    }
};
