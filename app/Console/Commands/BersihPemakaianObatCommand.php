<?php

namespace App\Console\Commands;

use App\Models\DetailPemakaianObat;
use App\Models\PemakaianObat;
use App\Services\StokService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BersihPemakaianObatCommand extends Command
{
    protected $signature = 'pemakaian:bersih
                            {--fasilitas= : Filter fasilitas_id (opsional, default semua faskes)}
                            {--before= : Hapus hanya data sebelum tanggal YYYY-MM-DD}
                            {--hard : Skip reverse stok, langsung truncate/hapus (stok tidak dikembalikan)}
                            {--dry-run : Tampilkan jumlah yang akan dihapus tanpa eksekusi}
                            {--force : Skip konfirmasi}';

    protected $description = 'Bersihkan data pemakaian obat untuk semua faskes (atau faskes tertentu). Default: reverse stok dulu agar konsisten.';

    public function handle(StokService $stokService): int
    {
        $fasilitasId = $this->option('fasilitas') ? (int) $this->option('fasilitas') : null;
        $before = $this->option('before');
        $isHard = (bool) $this->option('hard');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        if ($before !== null && ! strtotime((string) $before)) {
            $this->error("Format --before tidak valid. Gunakan YYYY-MM-DD. Diberikan: {$before}");

            return self::FAILURE;
        }

        $isFullClean = $fasilitasId === null && $before === null;

        $baseQuery = PemakaianObat::query()
            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->when($before, fn ($q) => $q->where('tanggal_pemakaian', '<', $before));

        $pemakaianCount = (clone $baseQuery)->count();

        if ($pemakaianCount === 0) {
            $this->info('Tidak ada data pemakaian yang cocok dengan filter.');

            return self::SUCCESS;
        }

        // Kumpulkan detail count & riwayat count untuk laporan dry-run
        $pemakaianIds = (clone $baseQuery)->pluck('id');

        $detailCount = DB::table('detail_pemakaian_obat')
            ->whereIn('pemakaian_id', $pemakaianIds)
            ->count();

        $riwayatCount = 0;
        if ($detailCount > 0) {
            $detailIds = DB::table('detail_pemakaian_obat')
                ->whereIn('pemakaian_id', $pemakaianIds)
                ->pluck('id');

            $riwayatCount = DB::table('riwayat_stok')
                ->where('referensi_type', DetailPemakaianObat::class)
                ->whereIn('referensi_id', $detailIds)
                ->count();
        }

        if ($isFullClean) {
            // Untuk full clean, hitung semua activity pemakaian_obat termasuk orphan
            $activityCount = DB::table('activity_log')
                ->where('log_name', 'pemakaian_obat')
                ->count();
        } else {
            $activityCount = DB::table('activity_log')
                ->where('log_name', 'pemakaian_obat')
                ->where(function ($q) use ($pemakaianIds, $detailCount): void {
                    $q->where(function ($qq) use ($pemakaianIds): void {
                        $qq->where('subject_type', PemakaianObat::class)
                            ->whereIn('subject_id', $pemakaianIds);
                    });
                    if ($detailCount > 0) {
                        $detailIdsInner = DB::table('detail_pemakaian_obat')
                            ->whereIn('pemakaian_id', $pemakaianIds)
                            ->pluck('id');
                        $q->orWhere(function ($qq) use ($detailIdsInner): void {
                            $qq->where('subject_type', DetailPemakaianObat::class)
                                ->whereIn('subject_id', $detailIdsInner);
                        });
                    }
                })
                ->count();
        }

        $filterLabel = $fasilitasId ? "fasilitas_id={$fasilitasId}" : 'semua faskes';
        $beforeLabel = $before ? ", sebelum {$before}" : '';
        $modeLabel = $isHard ? 'HARD (tanpa reverse stok)' : 'SOFT (reverse stok dulu)';

        $this->line("Filter: {$filterLabel}{$beforeLabel} | Mode: {$modeLabel}");
        $this->table(
            ['Tabel', 'Jumlah'],
            [
                ['pemakaian_obat', $pemakaianCount],
                ['detail_pemakaian_obat', $detailCount],
                ['riwayat_stok (ref DetailPemakaianObat)', $riwayatCount],
                ['activity_log (pemakaian_obat)', $activityCount],
            ]
        );

        if ($isDryRun) {
            $this->info('[DRY-RUN] Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $isForce && ! $this->confirm("Yakin hapus {$pemakaianCount} data pemakaian ({$filterLabel}{$beforeLabel}) dengan mode {$modeLabel}? Stok akan ".($isHard ? 'TIDAK dikembalikan' : 'dikembalikan via reverse').'.', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        // Simpan detailIds sebelum penghapusan untuk cleanup riwayat/activity
        $detailIds = $detailCount > 0
            ? DB::table('detail_pemakaian_obat')->whereIn('pemakaian_id', $pemakaianIds)->pluck('id')
            : collect();

        if (! $isHard) {
            $this->info("Memulai reverse stok untuk {$pemakaianCount} pemakaian (chunk 200)...");
            $bar = $this->output->createProgressBar($pemakaianCount);
            $bar->start();

            // Chunk by ID agar tidak OOM untuk data besar
            (clone $baseQuery)
                ->with('details')
                ->chunkById(200, function ($chunk) use ($stokService, $bar): void {
                    foreach ($chunk as $pemakaian) {
                        try {
                            $stokService->reversePemakaian($pemakaian->loadMissing('details'));
                        } catch (\Throwable $e) {
                            // Batch stok mungkin sudah terhapus/null — tetap lanjut, catat warning
                            $this->warn("Gagal reverse pemakaian ID {$pemakaian->id} ({$pemakaian->nomor_pemakaian}): {$e->getMessage()}");
                        }
                        $bar->advance();
                    }
                });

            $bar->finish();
            $this->newLine();
            $this->info('Reverse stok selesai.');

            // Reverse membuat riwayat baru (tipe masuk) dengan referensi detail yang sama — ambil ulang detailIds jika ada yang baru? Tidak perlu, referensi_id tetap sama.
            // Hitung ulang riwayat setelah reverse untuk memastikan semua terhapus (termasuk yang baru dibuat)
            $riwayatCountAfter = $detailIds->isNotEmpty()
                ? DB::table('riwayat_stok')
                    ->where('referensi_type', DetailPemakaianObat::class)
                    ->whereIn('referensi_id', $detailIds)
                    ->count()
                : 0;

            if ($riwayatCountAfter > 0) {
                $this->line("Menghapus {$riwayatCountAfter} riwayat_stok terkait...");
                DB::table('riwayat_stok')
                    ->where('referensi_type', DetailPemakaianObat::class)
                    ->whereIn('referensi_id', $detailIds)
                    ->delete();
            }
        } else {
            // Hard mode: langsung hapus riwayat tanpa reverse
            if ($detailIds->isNotEmpty() && $riwayatCount > 0) {
                $this->line("Mode HARD: menghapus {$riwayatCount} riwayat_stok tanpa reverse...");
                DB::table('riwayat_stok')
                    ->where('referensi_type', DetailPemakaianObat::class)
                    ->whereIn('referensi_id', $detailIds)
                    ->delete();
            }
        }

        // Hapus activity_log terkait (sebelum hapus pemakaian agar subject_id masih terlacak)
        if ($activityCount > 0) {
            $this->line("Menghapus {$activityCount} activity_log...");

            if ($isFullClean) {
                // Full clean: hapus semua log_name pemakaian_obat termasuk orphan
                DB::table('activity_log')
                    ->where('log_name', 'pemakaian_obat')
                    ->delete();
            } else {
                // Filtered: hanya hapus yang terkait dengan IDs terpilih
                DB::table('activity_log')
                    ->where('log_name', 'pemakaian_obat')
                    ->where('subject_type', PemakaianObat::class)
                    ->whereIn('subject_id', $pemakaianIds)
                    ->delete();

                if ($detailIds->isNotEmpty()) {
                    DB::table('activity_log')
                        ->where('log_name', 'pemakaian_obat')
                        ->where('subject_type', DetailPemakaianObat::class)
                        ->whereIn('subject_id', $detailIds)
                        ->delete();
                }
            }
        }

        // Hapus pemakaian_obat (cascade akan hapus detail_pemakaian_obat via FK)
        $this->line("Menghapus {$pemakaianCount} pemakaian_obat...");

        // Gunakan chunk delete untuk menghindari whereIn terlalu besar pada DB tertentu
        $pemakaianIds->chunk(1000)->each(function ($chunk): void {
            DB::table('pemakaian_obat')->whereIn('id', $chunk)->delete();
        });

        // Jika full bersih tanpa filter: reset auto increment (seperti Cleanup* test data)
        if ($isFullClean) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                try {
                    DB::statement('ALTER TABLE pemakaian_obat AUTO_INCREMENT = 1');
                    DB::statement('ALTER TABLE detail_pemakaian_obat AUTO_INCREMENT = 1');
                    // riwayat_stok auto_increment tidak di-reset agar histori lain tetap berlanjut, tapi boleh reset jika diminta hard full
                } catch (\Throwable $e) {
                    $this->warn('Gagal reset AUTO_INCREMENT: '.$e->getMessage());
                }
            } elseif ($driver === 'sqlite') {
                // sqlite_sequence reset
                try {
                    DB::table('sqlite_sequence')->where('name', 'pemakaian_obat')->delete();
                    DB::table('sqlite_sequence')->where('name', 'detail_pemakaian_obat')->delete();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $this->line('AUTO_INCREMENT di-reset untuk pemakaian_obat & detail_pemakaian_obat.');
        }

        $this->info("Selesai. {$pemakaianCount} pemakaian, {$detailCount} detail, {$riwayatCount} riwayat, {$activityCount} activity_log dibersihkan (mode: {$modeLabel}).");

        if (! $isHard) {
            $this->line('Stok & batch telah dikembalikan (reverse). Jika ada batch yang sebelumnya habis, status kembali tersedia bila stok >0.');
        } else {
            $this->warn('Mode HARD: stok & batch TIDAK dikembalikan. Jalankan rekap/stok opname jika perlu sinkronisasi.');
        }

        return self::SUCCESS;
    }
}
