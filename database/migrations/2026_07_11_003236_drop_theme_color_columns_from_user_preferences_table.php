<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'tema_warna',
                'color_primary',
                'color_topbar',
                'color_sidebar',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->enum('tema_warna', ['light', 'dark', 'auto'])->default('dark');
            $table->string('color_primary', 50)->default('amber');
            $table->string('color_topbar', 50)->default('transparent');
            $table->string('color_sidebar', 50)->default('transparent');
        });
    }
};
