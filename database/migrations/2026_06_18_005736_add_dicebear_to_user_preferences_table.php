<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->string('avatar_type', 50)->default('initials')->change();
            $table->string('avatar_dicebear_style', 50)->nullable()->default('avataaars');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('avatar_dicebear_style');
            $table->string('avatar_type', 50)->default('initials')->change();
        });
    }
};
