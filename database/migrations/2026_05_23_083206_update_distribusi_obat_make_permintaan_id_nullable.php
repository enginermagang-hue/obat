<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribusi_obat', function (Blueprint $table) {
            $table->foreignId('permintaan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('distribusi_obat', function (Blueprint $table) {
            $table->foreignId('permintaan_id')->nullable(false)->change();
        });
    }
};
