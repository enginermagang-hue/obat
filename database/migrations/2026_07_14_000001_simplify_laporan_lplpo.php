<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->foreignId('parent_lplpo_id')->nullable()->constrained('laporan_lplpo')->nullOnDelete()->after('id');
        });

        // Drop old unique constraint that included jenis_pengajuan
        // (jenis_pengajuan column still exists in DB but is no longer used)
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->dropIndex('lplpo_unique_per_jenis');
        });

        // No new unique constraint at DB level.
        // Revisi needs the same fasilitas+period as original,
        // and uniqueness is enforced at form validation level.
    }

    public function down(): void
    {
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->dropForeign(['parent_lplpo_id']);
            $table->dropColumn('parent_lplpo_id');
        });

        // Restore old unique constraint
        Schema::table('laporan_lplpo', function (Blueprint $table) {
            $table->unique(['fasilitas_id', 'periode_bulan', 'periode_tahun', 'jenis_pengajuan'], 'lplpo_unique_per_jenis');
        });
    }
};
