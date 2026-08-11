<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('color_topbar', 50)->default('transparent')->change();
            $table->string('color_sidebar', 50)->default('transparent')->change();
            $table->enum('tema_warna', ['light', 'dark', 'auto'])->default('dark')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('color_topbar', 50)->default('gray')->change();
            $table->string('color_sidebar', 50)->default('white')->change();
            $table->enum('tema_warna', ['light', 'dark', 'auto'])->default('auto')->change();
        });
    }
};
