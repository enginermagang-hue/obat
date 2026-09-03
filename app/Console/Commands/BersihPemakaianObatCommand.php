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
                            {--with-prediksi : Juga hapus model_prediksi & prediksi_kebutuhan ter-scope}
                            {--obat= : Filter obat_id untuk prediksi (opsional, hanya dengan --with-prediksi)}
                            {--dry-run : Tampilkan jumlah yang akan dihapus tanpa eksekusi}
                            {--force : Skip konfirmasi}';

    protected $description = 'Bersihkan data pemakaian obat untuk semua faskes (atau faskes tertentu). Default: reverse stok dulu agar konsisten. Gunakan --with-prediksi untuk ikut hapus prediksi (RKO prediksi_id di-null-kan).';

    public function handle(StokService $stokService): int
    {
        $fasilitasId = $this->option('fasilitas') ? (int) $this->option('fasilitas') : null;
        $before = $this->option('before');
        $isHard = (bool) $this->option('hard');
        $withPrediksi = (bool) $this->option('with-prediksi');
        $obatId = $this->option('obat') ? (int) $this->option('obat') : null;
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        if ($obatId !== null && ! $withPrediksi) {
            $this->warn('--obat diabaikan tanpa --with-prediksi.');
        }

        if ($before !== null && ! strtotime((string) $before)) {
            $this->error("Format --before tidak valid. Gunakan YYYY-MM-DD. Diberikan: {$before}");

            return self::FAILURE;
        }

        $isFullClean = $fasilitasId === null && $before === null;

        $baseQuery = PemakaianObat::query()
            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->when($before, fn ($q) => $q->where('tanggal_pemakaian', '<', $before));

        $pemakaianCount = (clone $baseQuery)->count();

        if ($pemakaianCount === 0 && ! $withPrediksi) {
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

        // Prediksi counts (jika --with-prediksi)
        $modelCount = 0;
        $prediksiCount = 0;
        $rkoNullCount = 0;
        $predActivityCount = 0;
        $modelIds = collect();
        $prediksiIds = collect();
        if ($withPrediksi) {
            $modelQuery = DB::table('model_prediksi')
                ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
                ->when($obatId, fn ($q) => $q->where('obat_id', $obatId))
                ->when($before, fn ($q) => $q->where('tanggal_training', '<', $before));
            $modelCount = (clone $modelQuery)->count();
            $modelIds = (clone $modelQuery)->pluck('id');
            if ($modelIds->isNotEmpty()) {
                $prediksiCount = DB::table('prediksi_kebutuhan')->whereIn('model_id', $modelIds)->count();
                $prediksiIds = DB::table('prediksi_kebutuhan')->whereIn('model_id', $modelIds)->pluck('id');
                $rkoNullCount = $prediksiIds->isNotEmpty() ? DB::table('detail_rko')->whereIn('prediksi_id', $prediksiIds)->count() : 0;
                $predActivityCount = DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->where(function ($q) use ($modelIds, $prediksiIds): void {
                    $q->where(function ($qq) use ($modelIds): void {
                        $qq->where('subject_type', 'App\\Models\\ModelPrediksi')->whereIn('subject_id', $modelIds);
                    })->orWhere(function ($qq) use ($prediksiIds): void {
                        $qq->where('subject_type', 'App\\Models\\PrediksiKebutuhan')->whereIn('subject_id', $prediksiIds);
                    });
                })->count();
                if ($isFullClean && $obatId === null) {
                    // Full clean: hitung semua prediksi termasuk yang mungkin orphan dari scope berbeda
                    $predActivityCount = DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->count();
                }
            } elseif ($isFullClean && $obatId === null) {
                $predActivityCount = DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->count();
            }
        }

        $filterLabel = $fasilitasId ? "fasilitas_id={$fasilitasId}" : 'semua faskes';
        $beforeLabel = $before ? ", sebelum {$before}" : '';
        $modeLabel = $isHard ? 'HARD (tanpa reverse stok)' : 'SOFT (reverse stok dulu)';
        $prediksiLabel = $withPrediksi ? ' + PREDIKSI' : '';

        $this->line("Filter: {$filterLabel}{$beforeLabel} | Mode: {$modeLabel}{$prediksiLabel}");
        $rows = [
            ['pemakaian_obat', $pemakaianCount],
            ['detail_pemakaian_obat', $detailCount],
            ['riwayat_stok (ref DetailPemakaianObat)', $riwayatCount],
            ['activity_log (pemakaian_obat)', $activityCount],
        ];
        if ($withPrediksi) {
            $rows[] = ['model_prediksi', $modelCount];
            $rows[] = ['prediksi_kebutuhan', $prediksiCount];
            $rows[] = ['detail_rko (prediksi_id akan di-null-kan)', $rkoNullCount];
            $rows[] = ['activity_log (prediksi_kebutuhan)', $predActivityCount];
        }
        $this->table(['Tabel', 'Jumlah'], $rows);

        if ($isDryRun) {
            $this->info('[DRY-RUN] Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        $confirmMsg = "Yakin hapus {$pemakaianCount} data pemakaian ({$filterLabel}{$beforeLabel}) dengan mode {$modeLabel}{$prediksiLabel}? Stok akan ".($isHard ? 'TIDAK dikembalikan' : 'dikembalikan via reverse').'.';
        if ($withPrediksi) {
            $confirmMsg .= " + {$modelCount} model & {$prediksiCount} prediksi akan dihapus (RKO {$rkoNullCount} di-null-kan).";
        }
        if (! $isForce && ! $this->confirm($confirmMsg, false)) {
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

        // Jika --with-prediksi: null-kan RKO lalu hapus prediksi & model
        if ($withPrediksi && ($modelCount > 0 || $prediksiCount > 0)) {
            DB::transaction(function () use ($prediksiIds, $modelIds, $rkoNullCount, $prediksiCount, $modelCount, $predActivityCount, $isFullClean, $obatId): void {
                if ($rkoNullCount > 0) {
                    $this->line("Meng-null-kan prediksi_id pada {$rkoNullCount} detail_rko...");
                    $prediksiIds->chunk(1000)->each(fn ($chunk) => DB::table('detail_rko')->whereIn('prediksi_id', $chunk)->update(['prediksi_id' => null]));
                }
                if ($prediksiCount > 0) {
                    $this->line("Menghapus {$prediksiCount} prediksi_kebutuhan...");
                    $prediksiIds->chunk(1000)->each(fn ($chunk) => DB::table('prediksi_kebutuhan')->whereIn('id', $chunk)->delete());
                }
                if ($predActivityCount > 0) {
                    $this->line("Menghapus {$predActivityCount} activity_log prediksi...");
                    if ($isFullClean && $obatId === null) {
                        DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->delete();
                    } else {
                        if ($modelIds->isNotEmpty()) {
                            DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->where('subject_type', 'App\\Models\\ModelPrediksi')->whereIn('subject_id', $modelIds)->delete();
                        }
                        if ($prediksiIds->isNotEmpty()) {
                            DB::table('activity_log')->where('log_name', 'prediksi_kebutuhan')->where('subject_type', 'App\\Models\\PrediksiKebutuhan')->whereIn('subject_id', $prediksiIds)->delete();
                        }
                    }
                }
                if ($modelCount > 0) {
                    $this->line("Menghapus {$modelCount} model_prediksi...");
                    $modelIds->chunk(1000)->each(fn ($chunk) => DB::table('model_prediksi')->whereIn('id', $chunk)->delete());
                }
            });
        }

        // Jika full bersih tanpa filter: reset auto increment (seperti Cleanup* test data)
        if ($isFullClean) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                try {
                    DB::statement('ALTER TABLE pemakaian_obat AUTO_INCREMENT = 1');
                    DB::statement('ALTER TABLE detail_pemakaian_obat AUTO_INCREMENT = 1');
                    if ($withPrediksi && $obatId === null) {
                        DB::statement('ALTER TABLE prediksi_kebutuhan AUTO_INCREMENT = 1');
                        DB::statement('ALTER TABLE model_prediksi AUTO_INCREMENT = 1');
                    }
                } catch (\Throwable $e) {
                    $this->warn('Gagal reset AUTO_INCREMENT: '.$e->getMessage());
                }
            } elseif ($driver === 'sqlite') {
                try {
                    DB::table('sqlite_sequence')->where('name', 'pemakaian_obat')->delete();
                    DB::table('sqlite_sequence')->where('name', 'detail_pemakaian_obat')->delete();
                    if ($withPrediksi && $obatId === null) {
                        DB::table('sqlite_sequence')->where('name', 'prediksi_kebutuhan')->delete();
                        DB::table('sqlite_sequence')->where('name', 'model_prediksi')->delete();
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $this->line('AUTO_INCREMENT di-reset untuk pemakaian_obat & detail_pemakaian_obat'.($withPrediksi && $obatId === null ? ' + prediksi/model.' : '.'));
        }

        $doneMsg = "Selesai. {$pemakaianCount} pemakaian, {$detailCount} detail, {$riwayatCount} riwayat, {$activityCount} activity_log dibersihkan (mode: {$modeLabel}).";
        if ($withPrediksi) {
            $doneMsg .= " Prediksi: {$modelCount} model, {$prediksiCount} prediksi, {$rkoNullCount} RKO di-null-kan, {$predActivityCount} activity_log.";
        }
        $this->info($doneMsg);

        if (! $isHard) {
            $this->line('Stok & batch telah dikembalikan (reverse). Jika ada batch yang sebelumnya habis, status kembali tersedia bila stok >0.');
        } else {
            $this->warn('Mode HARD: stok & batch TIDAK dikembalikan. Jalankan rekap/stok opname jika perlu sinkronisasi.');
        }

        return self::SUCCESS;
    }
}
