<?php

namespace App\Console\Commands;

use App\Services\KalkulasiStokMinimumService;
use Illuminate\Console\Command;

class KalkulasiStokMinimumCommand extends Command
{
    protected $signature = 'stok:kalkulasi-minimum
        {--gudang-only : Hanya kalkulasi stok_minimum untuk gudang}
        {--faskes-only : Hanya kalkulasi stok_minimum untuk faskes}';

    protected $description = 'Hitung ulang stok_minimum berdasarkan rata-rata pemakaian/distribusi';

    public function handle(KalkulasiStokMinimumService $service): int
    {
        $gudangOnly = $this->option('gudang-only');
        $faskesOnly = $this->option('faskes-only');

        if ($gudangOnly && $faskesOnly) {
            $this->error('Gunakan --gudang-only ATAU --faskes-only, tidak keduanya.');

            return static::FAILURE;
        }

        $ringkasan = $service->getRingkasan();

        if ($gudangOnly) {
            $updated = $service->kalkulasiGudang();
            $this->line('');
            $this->info("✅ Stok gudang: {$updated} obat diperbarui.");
            $this->line("   (dari {$ringkasan['gudang']['total_obat']} total obat)");

            return static::SUCCESS;
        }

        if ($faskesOnly) {
            $updated = $service->kalkulasiFaskes();
            $this->line('');
            $this->info("✅ Stok faskes: {$updated} record diperbarui.");
            $this->line("   ({$ringkasan['faskes']['total_faskes']} faskes, {$ringkasan['faskes']['total_record']} total record)");

            return static::SUCCESS;
        }

        // Kalkulasi semua
        $this->line('📊 Ringkasan sebelum kalkulasi:');
        $this->line("   Gudang: {$ringkasan['gudang']['total_obat']} obat");
        $this->line("   Faskes: {$ringkasan['faskes']['total_record']} record ({$ringkasan['faskes']['total_faskes']} faskes)");
        $this->line("   Safety factor gudang: {$ringkasan['gudang']['safety_factor']}×");
        $this->line("   Safety factor faskes: {$ringkasan['faskes']['safety_factor']}×");
        $this->line("   Periode: {$ringkasan['gudang']['periode_bulan']} bulan terakhir");
        $this->line('');

        $result = $service->kalkulasiSemua();

        $this->info("✅ Gudang: {$result['gudang']} obat diperbarui.");
        $this->info("✅ Faskes: {$result['faskes']} record diperbarui.");
        $this->line('');

        return static::SUCCESS;
    }
}
