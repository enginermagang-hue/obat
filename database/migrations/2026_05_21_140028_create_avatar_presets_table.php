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
        Schema::create('avatar_presets', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('file_path');
            $table->enum('kategori', ['hewan', 'profesi', 'abstrak', 'emoji', 'alam']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['kategori', 'is_active', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avatar_presets');
    }
};
