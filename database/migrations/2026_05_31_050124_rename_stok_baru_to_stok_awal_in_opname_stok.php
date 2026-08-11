<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename enum value 'stok_baru' to 'stok_awal' in opname_stok table.
     * Also update existing data.
     */
    public function up(): void
    {
        DB::statement("UPDATE opname_stok SET tipe = 'stok_awal' WHERE tipe = 'stok_baru'");

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE opname_stok MODIFY COLUMN tipe ENUM('penyesuaian', 'stok_awal') DEFAULT 'penyesuaian'");
        }
    }

    /**
     * Reverse: rename 'stok_awal' back to 'stok_baru'.
     */
    public function down(): void
    {
        DB::statement("UPDATE opname_stok SET tipe = 'stok_baru' WHERE tipe = 'stok_awal'");

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE opname_stok MODIFY COLUMN tipe ENUM('penyesuaian', 'stok_baru') DEFAULT 'penyesuaian'");
        }
    }
};
