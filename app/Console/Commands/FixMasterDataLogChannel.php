<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('activity:fix-master-data {--dry-run : Tampilkan rencana tanpa UPDATE} {--apply : Eksekusi UPDATE log_name}')]
#[Description('Migrasi entry activity_log dengan log_name=master_data ke channel yang sesuai per model')]
class FixMasterDataLogChannel extends Command
{
    private const MAPPING = [
        'App\Models\PenerimaanStok' => 'penerimaan_stok',
        'App\Models\DistribusiObat' => 'distribusi_obat',
        'App\Models\PermintaanObat' => 'permintaan_obat',
        'App\Models\User' => 'user_management',
        'App\Models\PemakaianObat' => 'pemakaian_obat',
        'App\Models\DetailPemakaianObat' => 'pemakaian_obat',
    ];

    public function handle(): int
    {
        $apply = $this->option('apply');
        $dryRun = $this->option('dry-run');

        $verb = $apply ? 'Memigrasi' : 'Akan migrasi';
        $total = 0;

        foreach (self::MAPPING as $subjectType => $newLogName) {
            $count = DB::table('activity_log')
                ->where('log_name', 'master_data')
                ->where('subject_type', $subjectType)
                ->count();

            if ($count === 0) {
                $this->line("  <fg=gray>SKIP</> {$subjectType} — tidak ada data");

                continue;
            }

            $total += $count;

            if ($apply) {
                DB::table('activity_log')
                    ->where('log_name', 'master_data')
                    ->where('subject_type', $subjectType)
                    ->update(['log_name' => $newLogName]);

                $this->info("  {$verb} {$count} baris: {$subjectType} -> {$newLogName}");
            } else {
                $this->line("  <fg=yellow>{$verb}</> {$count} baris: {$subjectType} -> <fg=green>{$newLogName}</>");
            }
        }

        if ($total === 0) {
            $this->info('Tidak ada data yang perlu dimigrasi.');
        } elseif ($apply) {
            $this->info("Selesai: {$total} baris dimigrasi.");
        } else {
            if (! $dryRun) {
                $this->warn("\nIni preview (dry-run). Jalankan dengan --apply untuk eksekusi.");
            }

            $this->info("Total: {$total} baris akan dimigrasi.");
        }

        return 0;
    }
}
