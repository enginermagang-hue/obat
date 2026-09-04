<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BersihDataTransaksiCommand extends Command
{
    protected $signature = 'data:bersih-transaksi
                            {--dry-run : Tampilkan jumlah yang akan dihapus tanpa eksekusi}
                            {--force : Skip konfirmasi (wajib di production)}';

    protected $description = 'Bersihkan seluruh data transaksi + sumber dana (stok, distribusi, laporan, AI, log). Master Pengguna, Obat, Supplier, dan Faskes dipertahankan.';

    /**
     * Tabel transaksi yang dihapus total, urut child → parent.
     *
     * @var list<string>
     */
    private const WIPE_TABLES = [
        // Detail transaksi
        'detail_penerimaan_stok',
        'detail_permintaan_obat',
        'detail_distribusi_obat',
        'detail_pemakaian_obat',
        'detail_opname_stok',
        'detail_retur_obat',
        'detail_lplpo',
        'detail_rko',
        'detail_neraca_sumber_dana',
        'detail_neraca_tahunan',
        // Inspeksi retur
        'inspeksi_retur',
        // Header transaksi & laporan
        'penerimaan_stok',
        'permintaan_obat',
        'distribusi_obat',
        'pemakaian_obat',
        'opname_stok',
        'retur_obat',
        'laporan_lplpo',
        'laporan_rko',
        'neraca_tahunan',
        // AI (prediksi dulu, baru model)
        'prediksi_kebutuhan',
        'model_prediksi',
        // Penggunaan dana & histori import, lalu master sumber dana
        'sumber_dana_penggunaan',
        'sumber_dana',
        'import_data_historis',
        // Riwayat & batch
        'riwayat_stok',
        'batch_stok',
        // Log, notifikasi, export/import, antrian
        'activity_log',
        'notifications',
        'exports',
        'imports',
        'failed_import_rows',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    /**
     * Tabel master yang dipertahankan (hanya ditampilkan sebagai info).
     *
     * @var list<string>
     */
    private const KEPT_TABLES = [
        'users',
        'obat',
        'suppliers',
        'fasilitas_kesehatan',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        if (app()->isProduction() && ! $isForce && ! $isDryRun) {
            $this->error('Command ini diblokir di production kecuali dengan --force. Backup database dulu (backup:run --only-db).');

            return self::FAILURE;
        }

        $wipeCounts = $this->countRows(self::WIPE_TABLES);
        $keptCounts = $this->countRows(self::KEPT_TABLES);
        $stokGudangCount = DB::table('stok_gudang')->count();
        $stokFaskesCount = DB::table('stok_faskes')->count();
        $modelFiles = $this->modelFilePaths();

        $this->line('Master yang <info>DIPERTAHANKAN</info>: Pengguna, Obat, Supplier, Faskes (+ pengaturan, roles).');
        $this->table(['Tabel (AKAN DIHAPUS)', 'Jumlah'], array_map(
            fn ($table, $count) => [$table, $count],
            array_keys($wipeCounts),
            array_values($wipeCounts)
        ));
        $this->table(['Tabel (DIPERTAHANKAN)', 'Jumlah'], array_map(
            fn ($table, $count) => [$table, $count],
            array_keys($keptCounts),
            array_values($keptCounts)
        ));
        $this->table(['Stok (akan di-nolkan)', 'Baris'], [
            ['stok_gudang (jumlah → 0)', $stokGudangCount],
            ['stok_faskes (jumlah → 0)', $stokFaskesCount],
            ['file model AI (ai-models/)', count($modelFiles)],
        ]);

        if ($isDryRun) {
            $this->info('[DRY-RUN] Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        $total = array_sum(array_values($wipeCounts));
        if (! $isForce && ! $this->confirm("Yakin hapus SELURUH data transaksi (~{$total} baris + file model AI)? Master (Pengguna, Obat, Supplier, Faskes) tetap. Aksi ini TIDAK bisa di-undo.", false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $this->disableForeignKeys();

        try {
            DB::transaction(function () use ($wipeCounts): void {
                foreach (array_keys($wipeCounts) as $table) {
                    DB::table($table)->delete();
                    $this->line("Dihapus: {$table}");
                }

                DB::table('stok_gudang')->update(['jumlah' => 0]);
                DB::table('stok_faskes')->update(['jumlah' => 0]);
                $this->line('Di-nolkan: stok_gudang.jumlah & stok_faskes.jumlah (stok_minimum dipertahankan).');
            });

            $this->resetAutoIncrement(array_keys($wipeCounts));

            foreach ($modelFiles as $path) {
                Storage::disk('local')->delete($path);
            }
            if ($modelFiles !== []) {
                $this->line('Dihapus: '.count($modelFiles).' file model AI.');
            }
        } finally {
            $this->enableForeignKeys();
        }

        $this->info("Selesai. ~{$total} baris transaksi dibersihkan; stok di-nolkan; master utuh.");

        return self::SUCCESS;
    }

    /**
     * Hitung baris per tabel yang diberikan (hanya yang ada di schema).
     *
     * @param  list<string>  $tables
     * @return array<string, int>
     */
    private function countRows(array $tables): array
    {
        $counts = [];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /** @return list<string> */
    private function modelFilePaths(): array
    {
        if (! Storage::disk('local')->exists('ai-models')) {
            return [];
        }

        return collect(Storage::disk('local')->files('ai-models'))
            ->filter(fn ($path) => str_ends_with($path, '.json'))
            ->values()
            ->all();
    }

    private function disableForeignKeys(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys=OFF');
        }
    }

    private function enableForeignKeys(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    /** @param list<string> $tables */
    private function resetAutoIncrement(array $tables): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                foreach ($tables as $table) {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                }
            } else {
                DB::table('sqlite_sequence')->whereIn('name', $tables)->delete();
            }
        } catch (\Throwable $e) {
            $this->warn('Gagal reset AUTO_INCREMENT: '.$e->getMessage());
        }
    }
}
