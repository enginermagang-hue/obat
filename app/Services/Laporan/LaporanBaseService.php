<?php

namespace App\Services\Laporan;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class LaporanBaseService
{
    /**
     * Query builder for stock data source (riwayat_stok).
     */
    abstract protected function queryRiwayatStok(
        ?int $fasilitasId,
        int $obatId,
        int $tahun,
        ?int $bulan = null,
    ): Builder;

    /**
     * Human-readable period label for the report.
     */
    abstract protected function getPeriodeLabel(?int $fasilitasId, int $tahun, ?int $bulan = null): string;

    /**
     * Collect distinct obat_ids that have riwayat_stok entries.
     */
    protected function getObatIdsFromRiwayat(Builder $baseQuery, int $tahun, ?int $bulan = null): Collection
    {
        $query = (clone $baseQuery)->whereYear('tanggal', $tahun);

        if ($bulan !== null) {
            $query->whereMonth('tanggal', $bulan);
        }

        return $query->distinct()->pluck('obat_id');
    }

    /**
     * Stok Optimum = Pemakaian rata-rata per bulan + 20% stok pengaman.
     */
    protected function hitungStokOptimum(int $totalPemakaian, int $periodeBulan = 12): int
    {
        $rataBulan = max(0, $totalPemakaian / max(1, $periodeBulan));

        return (int) ceil($rataBulan * 1.2);
    }

    /**
     * Permintaan = 3 × Pemakaian rata-rata per bulan - Stok Akhir.
     */
    protected function hitungPermintaan(int $totalPemakaian, int $stokAkhir, int $periodeBulan = 12): int
    {
        $rataBulan = max(0, $totalPemakaian / max(1, $periodeBulan));

        return max(0, (int) ceil($rataBulan * 3) - $stokAkhir);
    }
}
