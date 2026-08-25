<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupReturTestData extends Command
{
    protected $signature = 'test:cleanup-retur';

    protected $description = 'Cleanup retur test data';

    public function handle(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('detail_retur_obat')->truncate();
        DB::table('retur_obat')->truncate();
        DB::table('detail_distribusi_obat')->where('distribusi_id', function ($q) {
            $q->select('id')->from('distribusi_obat')->where('nomor_surat_jalan', 'SJ-E2E-TEST-001');
        })->delete();
        DB::table('distribusi_obat')->where('nomor_surat_jalan', 'SJ-E2E-TEST-001')->delete();
        DB::table('stok_faskes')->where('fasilitas_id', 1)->delete();
        DB::table('batch_stok')->where('batch_number', 'E2E-BATCH-001')->delete();
        DB::table('retur_obat')->where('id', '>', 0)->delete();
        DB::statement('ALTER TABLE retur_obat AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE detail_retur_obat AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE distribusi_obat AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE batch_stok AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE stok_faskes AUTO_INCREMENT = 1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Retur test data cleaned and auto_increment reset');
    }
}
