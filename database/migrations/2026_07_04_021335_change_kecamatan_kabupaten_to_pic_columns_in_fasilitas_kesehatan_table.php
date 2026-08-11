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
        Schema::table('fasilitas_kesehatan', function (Blueprint $table) {
            $table->dropColumn(['kecamatan', 'kabupaten']);
            $table->string('pic')->nullable()->after('alamat');
            $table->string('kontak_pic')->nullable()->after('pic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fasilitas_kesehatan', function (Blueprint $table) {
            $table->string('kecamatan')->nullable()->after('alamat');
            $table->string('kabupaten')->nullable()->after('kecamatan');
            $table->dropColumn(['pic', 'kontak_pic']);
        });
    }
};
