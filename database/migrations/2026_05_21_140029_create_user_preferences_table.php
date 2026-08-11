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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('avatar_type', ['upload', 'preset', 'initials'])->default('initials');
            $table->string('avatar_path')->nullable();
            $table->enum('tema_warna', ['light', 'dark', 'auto'])->default('auto');
            $table->enum('posisi_navbar', ['sidebar', 'topbar'])->default('sidebar');
            $table->boolean('sidebar_collapsed')->default(true);
            $table->enum('bahasa', ['id', 'en'])->default('id');
            $table->integer('items_per_halaman')->default(10);
            $table->boolean('notifikasi_email')->default(true);
            $table->boolean('notifikasi_browser')->default(true);
            $table->timestamps();
            $table->index(['avatar_type', 'bahasa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
