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
        Schema::table('model_prediksi', function (Blueprint $table) {
            $table->string('model_path')->nullable()->after('model_data');
            $table->decimal('mae', 10, 2)->nullable()->after('akurasi_r2');
            $table->decimal('mape', 5, 2)->nullable()->after('mae');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_prediksi', function (Blueprint $table) {
            $table->dropColumn(['model_path', 'mae', 'mape']);
        });
    }
};
