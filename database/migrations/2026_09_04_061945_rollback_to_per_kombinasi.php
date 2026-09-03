<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_prediksi')) {
            return;
        }
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('model_prediksi')->whereNull('obat_id')->delete();

            return;
        }
        $hasFaskesUnique = collect(DB::select("SHOW INDEX FROM model_prediksi WHERE Key_name='uq_model_prediksi_faskes'"))->isNotEmpty();
        if ($hasFaskesUnique) {
            DB::table('model_prediksi')->whereNull('obat_id')->delete();
            // Need tmp index for fasilitas_id FK before dropping unique
            $hasTmpFasilitas = collect(DB::select("SHOW INDEX FROM model_prediksi WHERE Key_name='tmp_fasilitas_id_idx'"))->isNotEmpty();
            if (! $hasTmpFasilitas) {
                DB::statement('ALTER TABLE model_prediksi ADD INDEX tmp_fasilitas_id_idx (fasilitas_id)');
            }
            DB::statement('ALTER TABLE model_prediksi DROP INDEX uq_model_prediksi_faskes');
            DB::statement('ALTER TABLE model_prediksi MODIFY obat_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE model_prediksi ADD UNIQUE KEY model_prediksi_fasilitas_id_obat_id_unique (fasilitas_id, obat_id)');
            try {
                DB::statement('ALTER TABLE model_prediksi DROP INDEX tmp_fasilitas_id_idx');
            } catch (Throwable $e) {
            }
            // Ensure obat_id has index for its FK (unique composite covers fasilitas_id prefix, not obat_id alone)
            $hasTmpObat = collect(DB::select("SHOW INDEX FROM model_prediksi WHERE Key_name='tmp_obat_id_idx'"))->isNotEmpty();
            if (! $hasTmpObat) {
                DB::statement('ALTER TABLE model_prediksi ADD INDEX tmp_obat_id_idx (obat_id)');
            }
        }
    }

    public function down(): void {}
};
