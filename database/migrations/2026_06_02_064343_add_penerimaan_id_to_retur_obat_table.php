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
        Schema::table('retur_obat', function (Blueprint $table) {
            $table->foreignId('penerimaan_id')->nullable()->after('distribusi_id')->constrained('penerimaan_stok')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retur_obat', function (Blueprint $table) {
            $table->dropForeign(['penerimaan_id']);
            $table->dropColumn('penerimaan_id');
        });
    }
};
