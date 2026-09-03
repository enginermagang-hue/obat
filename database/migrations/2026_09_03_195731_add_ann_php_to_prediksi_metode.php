<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return; // enum is text check in sqlite, no need
        }
        DB::statement("ALTER TABLE prediksi_kebutuhan MODIFY metode ENUM('ai_gradient_boost','ai_random_forest','moving_average','manual','ann_php')");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE prediksi_kebutuhan MODIFY metode ENUM('ai_gradient_boost','ai_random_forest','moving_average','manual')");
    }
};
