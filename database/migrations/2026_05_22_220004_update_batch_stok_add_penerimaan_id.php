<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: 'supplier' column dropped and 'penerimaan_id' column added
     * in a prior failed run. This handles fresh-install and existing-db scenarios.
     */
    public function up(): void
    {
        Schema::table('batch_stok', function (Blueprint $table) {
            // Drop 'supplier' column if it still exists (fresh-install compat)
            if (Schema::hasColumn('batch_stok', 'supplier')) {
                $table->dropColumn('supplier');
            }

            // Add 'penerimaan_id' column if it does not exist yet
            if (! Schema::hasColumn('batch_stok', 'penerimaan_id')) {
                $table->foreignId('penerimaan_id')
                    ->nullable()
                    ->constrained('penerimaan_stok')
                    ->nullOnDelete();
            }

            // Add a standalone index on penerimaan_id for query performance
            // (the original compound index stays as-is because MySQL FK constraints depend on it)
            $table->index('penerimaan_id', 'idx_batch_penerimaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batch_stok', function (Blueprint $table) {
            // Remove 'penerimaan_id' column — drop FK first, then index, then column
            if (Schema::hasColumn('batch_stok', 'penerimaan_id')) {
                $table->dropForeign(['penerimaan_id']);
                $table->dropIndex('idx_batch_penerimaan');
                $table->dropColumn('penerimaan_id');
            }

            // Restore 'supplier' column
            if (! Schema::hasColumn('batch_stok', 'supplier')) {
                $table->string('supplier')->nullable()->after('tanggal_masuk');
            }
        });
    }
};
