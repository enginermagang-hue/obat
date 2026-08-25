<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPenerimaanTestData extends Command
{
    protected $signature = 'test:cleanup-penerimaan';

    protected $description = 'Cleanup penerimaan test data';

    public function handle(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('detail_penerimaan_stok')
            ->whereIn('penerimaan_id', function ($q) {
                $q->select('id')->from('penerimaan_stok')->where('nomor_penerimaan', 'like', 'E2E-PNM-%');
            })
            ->delete();
        DB::table('penerimaan_stok')->where('nomor_penerimaan', 'like', 'E2E-PNM-%')->delete();
        DB::table('batch_stok')->where('batch_number', 'like', 'E2E-PNM-%')->delete();
        DB::table('penerimaan_stok')->where('id', '>', 0)->delete();
        DB::statement('ALTER TABLE penerimaan_stok AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE detail_penerimaan_stok AUTO_INCREMENT = 1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Penerimaan test data cleaned and auto_increment reset');
    }
}
