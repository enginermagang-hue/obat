<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE avatar_presets MODIFY COLUMN kategori ENUM('hewan', 'profesi', 'abstrak', 'emoji', 'alam', 'boy', 'girl') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE avatar_presets MODIFY COLUMN kategori ENUM('hewan', 'profesi', 'abstrak', 'emoji', 'alam') NOT NULL");
    }
};
