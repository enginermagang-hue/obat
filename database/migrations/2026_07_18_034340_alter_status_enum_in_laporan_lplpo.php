<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE laporan_lplpo MODIFY COLUMN status ENUM('draft', 'selesai') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE laporan_lplpo MODIFY COLUMN status ENUM('draft', 'diajukan', 'disetujui', 'ditolak') NOT NULL DEFAULT 'draft'");
        }
    }
};
