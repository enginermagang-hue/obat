<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('color_primary', 50)->default('amber')->after('tema_warna');
            $table->string('color_topbar', 50)->default('gray')->after('color_primary');
            $table->string('color_sidebar', 50)->default('white')->after('color_topbar');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn(['color_primary', 'color_topbar', 'color_sidebar']);
        });
    }
};
