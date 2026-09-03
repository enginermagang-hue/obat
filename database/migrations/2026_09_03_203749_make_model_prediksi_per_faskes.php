<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_prediksi')) {
            return;
        }
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // Deprecated: per-faskes architecture reverted to per-kombinasi (2026-09-04). Keep no-op to preserve fresh.

    }

    public function down(): void {}
};
