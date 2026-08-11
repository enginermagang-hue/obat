<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructure `pemakaian_obat` table to be a header-only table:
     * - Add new columns: nomor_pemakaian, nama_pasien, no_rekam_medis
     * - Drop old columns: obat_id, batch_id, jumlah, jumlah_pasien (moved to detail_pemakaian_obat)
     * - Add unique index on nomor_pemakaian
     * - Add helper index for common queries
     */
    public function up(): void
    {
        // Safety check: abort if table has data (migration intended for empty table)
        $rowCount = DB::table('pemakaian_obat')->count();
        if ($rowCount > 0) {
            throw new RuntimeException(
                "Cannot restructure pemakaian_obat: table has {$rowCount} rows. "
                .'Data migration must be done manually before running this migration.'
            );
        }

        Schema::table('pemakaian_obat', function (Blueprint $table) {
            // 1. Add new columns
            $table->string('nomor_pemakaian', 50)->nullable()->after('id');
            $table->string('nama_pasien', 255)->nullable()->after('jenis_pelayanan');
            $table->string('no_rekam_medis', 50)->nullable()->after('nama_pasien');

            // 2. Drop old FKs first (before dropping columns)
            $table->dropForeign(['obat_id']);
            $table->dropForeign(['batch_id']);
        });

        // 3. Drop old composite index that referenced obat_id.
        //    MySQL auto-drops indexes when a column is dropped; SQLite does not.
        //    So only drop the index explicitly on SQLite to avoid the cross-DB mismatch.
        if (DB::getDriverName() === 'sqlite' && Schema::hasIndex('pemakaian_obat', 'idx_pemakaian_faskes_obat')) {
            Schema::table('pemakaian_obat', function (Blueprint $table) {
                $table->dropIndex('idx_pemakaian_faskes_obat');
            });
        }

        Schema::table('pemakaian_obat', function (Blueprint $table) {
            // 4. Drop old columns (moved to detail_pemakaian_obat)
            $table->dropColumn(['obat_id', 'batch_id', 'jumlah', 'jumlah_pasien']);
        });

        Schema::table('pemakaian_obat', function (Blueprint $table) {
            // 4. Add unique index on nomor_pemakaian
            $table->unique('nomor_pemakaian', 'pemakaian_obat_nomor_unique');
        });

        // 5. Add composite index for common queries (replacing old idx_pemakaian_faskes_obat)
        Schema::table('pemakaian_obat', function (Blueprint $table) {
            $table->index(
                ['fasilitas_id', 'tanggal_pemakaian', 'jenis_pelayanan'],
                'idx_pemakaian_header_v2'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemakaian_obat', function (Blueprint $table) {
            $table->dropUnique('pemakaian_obat_nomor_unique');
            $table->dropIndex('idx_pemakaian_header_v2');
        });

        Schema::table('pemakaian_obat', function (Blueprint $table) {
            $table->dropColumn(['nomor_pemakaian', 'nama_pasien', 'no_rekam_medis']);

            $table->foreignId('obat_id')
                ->after('fasilitas_id')
                ->constrained('obat')
                ->cascadeOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->after('obat_id')
                ->constrained('batch_stok')
                ->nullOnDelete();
            $table->unsignedInteger('jumlah')->after('jenis_pelayanan');
            $table->unsignedInteger('jumlah_pasien')->nullable()->after('jumlah');
        });

        Schema::table('pemakaian_obat', function (Blueprint $table) {
            $table->index(
                ['fasilitas_id', 'obat_id', 'tanggal_pemakaian', 'jenis_pelayanan', 'batch_id'],
                'idx_pemakaian_faskes_obat'
            );
        });
    }
};
