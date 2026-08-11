<?php

namespace App\Services;

use App\Models\BatchStok;
use App\Models\DetailNeracaTahunan;
use App\Models\NeracaTahunan;
use App\Models\Obat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\SumberDana;
use App\Services\Laporan\LaporanBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NeracaTahunanService extends LaporanBaseService
{
    /**
     * Generate annual stock balance details for a given NeracaTahunan record.
     */
    public function generate(NeracaTahunan $neraca): void
    {
        $fasilitasId = $neraca->fasilitas_id;
        $tahun = $neraca->tahun;

        $obatIdsFromRiwayat = $this->getObatIdsFromRiwayat(
            RiwayatStok::query()->when(
                is_null($fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
            ),
            $tahun,
        );

        $obatIdsFromStok = is_null($fasilitasId)
            ? StokGudang::where('jumlah', '>', 0)->pluck('obat_id')
            : StokFaskes::where('fasilitas_id', $fasilitasId)
                ->where('jumlah', '>', 0)
                ->pluck('obat_id');

        $obatIds = $obatIdsFromRiwayat
            ->merge($obatIdsFromStok)
            ->unique()
            ->values();

        if ($obatIds->isEmpty()) {
            return;
        }

        $obatMap = Obat::whereIn('id', $obatIds)
            ->pluck('harga_satuan', 'id');

        DB::transaction(function () use ($neraca, $fasilitasId, $tahun, $obatIds, $obatMap) {
            $neraca->details()->delete();

            foreach ($obatIds as $obatId) {
                $detailData = $this->calculateDetail(
                    neracaId: $neraca->id,
                    obatId: $obatId,
                    fasilitasId: $fasilitasId,
                    tahun: $tahun,
                    hargaSatuan: $obatMap->get($obatId),
                );

                $detail = DetailNeracaTahunan::create($detailData);

                // Calculate and store per-sumber-dana breakdown
                $sumberDanaData = $this->calculatePerSumberDana(
                    obatId: $obatId,
                    fasilitasId: $fasilitasId,
                    tahun: $tahun,
                    hargaSatuan: $obatMap->get($obatId),
                );

                if (filled($sumberDanaData)) {
                    foreach ($sumberDanaData as $sdData) {
                        $detail->sumberDanaDetails()->create($sdData);
                    }
                } else {
                    $detail->sumberDanaDetails()->create([
                        'sumber_dana_id' => null,
                        'stok_awal_jumlah' => $detailData['stok_awal'] ?? 0,
                        'stok_awal_nilai' => ($detailData['stok_awal'] ?? 0) * ($detailData['harga_satuan'] ?? 0),
                        'masuk_jumlah' => $detailData['total_masuk'] ?? 0,
                        'masuk_nilai' => ($detailData['total_masuk'] ?? 0) * ($detailData['harga_satuan'] ?? 0),
                        'keluar_jumlah' => $detailData['total_keluar'] ?? 0,
                        'keluar_nilai' => ($detailData['total_keluar'] ?? 0) * ($detailData['harga_satuan'] ?? 0),
                        'stok_akhir_jumlah' => $detailData['stok_akhir'] ?? 0,
                        'stok_akhir_nilai' => ($detailData['stok_akhir'] ?? 0) * ($detailData['harga_satuan'] ?? 0),
                    ]);
                }
            }
        });
    }

    protected function queryRiwayatStok(
        ?int $fasilitasId,
        int $obatId,
        int $tahun,
        ?int $bulan = null,
    ): Builder {
        $query = RiwayatStok::where('obat_id', $obatId)
            ->when(
                is_null($fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
            );

        return $query;
    }

    protected function getPeriodeLabel(?int $fasilitasId, int $tahun, ?int $bulan = null): string
    {
        return "Tahun {$tahun}";
    }

    private function calculateDetail(
        int $neracaId,
        int $obatId,
        ?int $fasilitasId,
        int $tahun,
        ?float $hargaSatuan,
    ): array {
        $baseQuery = $this->queryRiwayatStok($fasilitasId, $obatId, $tahun);

        $stokAwalRecord = (clone $baseQuery)
            ->whereDate('tanggal', '<', "{$tahun}-01-01")
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $stokAwal = $stokAwalRecord?->stok_sesudah ?? 0;

        $totalMasuk = abs((clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->whereIn('tipe', ['masuk', 'distribusi_masuk'])
            ->sum('jumlah'));

        $totalKeluar = abs((clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'hilang', 'expired'])
            ->sum('jumlah'));

        $stokAkhirRecord = (clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($stokAkhirRecord !== null) {
            $stokAkhir = $stokAkhirRecord->stok_sesudah;
        } elseif ($stokAwalRecord === null) {
            $stokAkhir = $this->getCurrentStock($obatId, $fasilitasId);
        } else {
            $stokAkhir = $stokAwal;
        }

        $nilaiStok = $hargaSatuan !== null
            ? $stokAkhir * $hargaSatuan
            : null;

        $stokOptimum = $this->hitungStokOptimum($totalKeluar, 12);
        $permintaan = $this->hitungPermintaan($totalKeluar, $stokAkhir, 12);

        return [
            'neraca_id' => $neracaId,
            'obat_id' => $obatId,
            'stok_awal' => $stokAwal,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'stok_akhir' => $stokAkhir,
            'stok_optimum' => $stokOptimum,
            'permintaan' => $permintaan,
            'harga_satuan' => $hargaSatuan,
            'nilai_stok' => $nilaiStok,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function getCurrentStock(int $obatId, ?int $fasilitasId): int
    {
        if (is_null($fasilitasId)) {
            return StokGudang::where('obat_id', $obatId)->value('jumlah') ?? 0;
        }

        return StokFaskes::where('fasilitas_id', $fasilitasId)
            ->where('obat_id', $obatId)
            ->value('jumlah') ?? 0;
    }

    /**
     * Calculate stock movement per sumber dana for a given obat.
     */
    private function calculatePerSumberDana(
        int $obatId,
        ?int $fasilitasId,
        int $tahun,
        ?float $hargaSatuan,
    ): array {
        $yearFilter = function ($q) use ($fasilitasId) {
            if (is_null($fasilitasId)) {
                $q->whereNull('rs.fasilitas_id')->orWhere('rs.fasilitas_id', '');
            } else {
                $q->where('rs.fasilitas_id', $fasilitasId);
            }
        };

        // SD IDs from penerimaan (masuk) in this year
        $penerimaanSdIds = DB::table('riwayat_stok as rs')
            ->join('penerimaan_stok as ps', function ($join) {
                $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\PenerimaanStok'"))
                    ->on('rs.referensi_id', '=', 'ps.id');
            })
            ->where('rs.obat_id', $obatId)
            ->whereYear('rs.tanggal', $tahun)
            ->where('rs.tipe', 'masuk')
            ->where($yearFilter)
            ->whereNotNull('ps.sumber_dana_id')
            ->distinct()
            ->pluck('ps.sumber_dana_id');

        // SD IDs from distribusi (masuk & keluar) in this year via batch
        $distribusiSdIds = DB::table('riwayat_stok as rs')
            ->join('detail_distribusi_obat as ddo', function ($join) use ($obatId) {
                $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\DistribusiObat'"))
                    ->on('rs.referensi_id', '=', 'ddo.distribusi_id')
                    ->where('ddo.obat_id', $obatId);
            })
            ->join('batch_stok as bs', 'ddo.batch_id', '=', 'bs.id')
            ->where('rs.obat_id', $obatId)
            ->whereYear('rs.tanggal', $tahun)
            ->where($yearFilter)
            ->whereNotNull('bs.sumber_dana_id')
            ->distinct()
            ->pluck('bs.sumber_dana_id');

        // SD IDs from pemakaian (keluar) in this year via batch
        $pemakaianSdIds = DB::table('riwayat_stok as rs')
            ->join('detail_pemakaian_obat as dpo', function ($join) {
                $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\DetailPemakaianObat'"))
                    ->on('rs.referensi_id', '=', 'dpo.id');
            })
            ->join('batch_stok as bs', 'dpo.batch_id', '=', 'bs.id')
            ->where('rs.obat_id', $obatId)
            ->whereYear('rs.tanggal', $tahun)
            ->where($yearFilter)
            ->whereNotNull('bs.sumber_dana_id')
            ->distinct()
            ->pluck('bs.sumber_dana_id');

        $allSdIds = $penerimaanSdIds->merge($distribusiSdIds)->merge($pemakaianSdIds)->unique()->values();

        if ($allSdIds->isEmpty()) {
            return [];
        }

        $sumberDanaList = SumberDana::whereIn('id', $allSdIds)->where('tahun', $tahun)->get();

        if ($sumberDanaList->isEmpty()) {
            return [];
        }

        $results = [];

        foreach ($sumberDanaList as $sd) {
            // Stok akhir per SD from current batch stock
            $stokAkhirJumlah = BatchStok::where('obat_id', $obatId)
                ->where('sumber_dana_id', $sd->id)
                ->when(is_null($fasilitasId), function ($q) {
                    $q->whereNull('fasilitas_id')->orWhere('fasilitas_id', '');
                }, function ($q) use ($fasilitasId) {
                    $q->where('fasilitas_id', $fasilitasId);
                })
                ->where('status', 'tersedia')
                ->sum('jumlah');

            // Masuk (penerimaan langsung) per sumber dana
            $masukPenerimaan = DB::table('riwayat_stok as rs')
                ->join('penerimaan_stok as ps', function ($join) {
                    $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\PenerimaanStok'"))
                        ->on('rs.referensi_id', '=', 'ps.id');
                })
                ->where('rs.obat_id', $obatId)
                ->where('ps.sumber_dana_id', $sd->id)
                ->whereYear('rs.tanggal', $tahun)
                ->where('rs.tipe', 'masuk')
                ->where($yearFilter)
                ->sum(DB::raw('ABS(rs.jumlah)'));

            // Masuk (distribusi masuk ke faskes) per sumber dana via batch
            $masukDistribusi = DB::table('riwayat_stok as rs')
                ->join('detail_distribusi_obat as ddo', function ($join) use ($obatId) {
                    $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\DistribusiObat'"))
                        ->on('rs.referensi_id', '=', 'ddo.distribusi_id')
                        ->where('ddo.obat_id', $obatId);
                })
                ->join('batch_stok as bs', 'ddo.batch_id', '=', 'bs.id')
                ->where('rs.obat_id', $obatId)
                ->where('bs.sumber_dana_id', $sd->id)
                ->whereYear('rs.tanggal', $tahun)
                ->where('rs.tipe', 'distribusi_masuk')
                ->where($yearFilter)
                ->sum(DB::raw('ABS(rs.jumlah)'));

            $masukJumlah = $masukPenerimaan + $masukDistribusi;

            // Keluar (pemakaian) per sumber dana via batch_id
            $keluarJumlah = DB::table('riwayat_stok as rs')
                ->join('detail_pemakaian_obat as dpo', function ($join) {
                    $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\DetailPemakaianObat'"))
                        ->on('rs.referensi_id', '=', 'dpo.id');
                })
                ->join('batch_stok as bs', 'dpo.batch_id', '=', 'bs.id')
                ->where('rs.obat_id', $obatId)
                ->where('bs.sumber_dana_id', $sd->id)
                ->whereYear('rs.tanggal', $tahun)
                ->whereIn('rs.tipe', ['keluar'])
                ->where($yearFilter)
                ->sum(DB::raw('ABS(rs.jumlah)'));

            // Distribusi keluar per sumber dana
            $distribusiKeluarJumlah = DB::table('riwayat_stok as rs')
                ->join('detail_distribusi_obat as ddo', function ($join) use ($obatId) {
                    $join->on('rs.referensi_type', '=', DB::raw("'App\\\\Models\\\\DistribusiObat'"))
                        ->on('rs.referensi_id', '=', 'ddo.distribusi_id')
                        ->where('ddo.obat_id', $obatId);
                })
                ->join('batch_stok as bs', 'ddo.batch_id', '=', 'bs.id')
                ->where('rs.obat_id', $obatId)
                ->where('bs.sumber_dana_id', $sd->id)
                ->whereYear('rs.tanggal', $tahun)
                ->whereIn('rs.tipe', ['distribusi_keluar'])
                ->where($yearFilter)
                ->sum(DB::raw('ABS(rs.jumlah)'));

            $totalKeluarJumlah = $keluarJumlah + $distribusiKeluarJumlah;

            // Stok awal derived: awal = akhir - masuk + keluar
            $stokAwalJumlah = $stokAkhirJumlah - $masukJumlah + $totalKeluarJumlah;

            if ($stokAwalJumlah > 0 || $masukJumlah > 0 || $totalKeluarJumlah > 0 || $stokAkhirJumlah > 0) {
                $results[] = [
                    'sumber_dana_id' => $sd->id,
                    'stok_awal_jumlah' => max(0, (int) $stokAwalJumlah),
                    'stok_awal_nilai' => max(0, $stokAwalJumlah * ($hargaSatuan ?? 0)),
                    'masuk_jumlah' => (int) $masukJumlah,
                    'masuk_nilai' => $masukJumlah * ($hargaSatuan ?? 0),
                    'keluar_jumlah' => (int) $totalKeluarJumlah,
                    'keluar_nilai' => $totalKeluarJumlah * ($hargaSatuan ?? 0),
                    'stok_akhir_jumlah' => (int) $stokAkhirJumlah,
                    'stok_akhir_nilai' => $stokAkhirJumlah * ($hargaSatuan ?? 0),
                ];
            }
        }

        return $results;
    }
}
