<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sumber_dana_penggunaan MODIFY tipe ENUM('perencanaan', 'realisasi', 'alokasi') NOT NULL");
        }
        // SQLite stores as varchar — no constraint to modify
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sumber_dana_penggunaan MODIFY tipe ENUM('perencanaan', 'realisasi') NOT NULL");
        }
    }
};
