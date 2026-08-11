<?php

namespace App\Console\Commands;

use App\Models\OpnameStok;
use App\Models\RiwayatStok;
use App\Services\StokService;
use Illuminate\Console\Command;

class BackfillStokOpname extends Command
{
    protected $signature = 'opname:backfill-stok';

    protected $description = 'Backfill stok agregat (StokFaskes/StokGudang) dari opname stok_awal/stok_baru yang terdampat bug';

    public function handle(StokService $service): int
    {
        $this->info('Mencari opname stok_awal/stok_baru dengan status selesai...');

        $opnames = OpnameStok::whereIn('tipe', ['stok_awal', 'stok_baru'])
            ->where('status', 'selesai')
            ->orderBy('id')
            ->get();

        $this->info("Ditemukan {$opnames->count()} record. Memeriksa yang sudah diproses...\n");

        $skipped = 0;
        $processed = 0;
        $failed = 0;

        foreach ($opnames as $opname) {
            $alreadyProcessed = RiwayatStok::where('referensi_type', OpnameStok::class)
                ->where('referensi_id', $opname->id)
                ->exists();

            if ($alreadyProcessed) {
                $this->line("  <fg=gray>SKIP</> #{$opname->id} {$opname->nomor_opname} — sudah ada riwayat");
                $skipped++;

                continue;
            }

            try {
                $this->line("  <fg=blue>PROCESS</> #{$opname->id} {$opname->nomor_opname} ... ", false);

                $opname->loadMissing('details');
                foreach ($opname->details as $detail) {
                    if ($detail->selisih === 0) {
                        $detail->update(['selisih' => $detail->stok_fisik]);
                    }
                }

                $service->prosesOpnameSelesai($opname);

                $this->line('<fg=green>OK</>');
                $processed++;
            } catch (\Throwable $e) {
                $this->line("<fg=red>FAIL</> {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\nSelesai: {$processed} diproses, {$skipped} dilewati, {$failed} gagal.");

        if ($failed > 0) {
            $this->warn('Ada error. Cek log untuk detail.');

            return 1;
        }

        return 0;
    }
}
