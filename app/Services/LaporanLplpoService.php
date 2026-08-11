<?php

namespace App\Services;

use App\Models\DetailLplpo;
use App\Models\LaporanLplpo;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Services\Laporan\LaporanBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LaporanLplpoService extends LaporanBaseService
{
    /**
     * Generate LPLPO detail items from stock history for a given period.
     */
    public function generate(LaporanLplpo $laporan): void
    {
        $fasilitasId = $laporan->fasilitas_id;
        $tahun = $laporan->periode_tahun;
        $bulan = $laporan->periode_bulan;

        // Collect obat_ids that have stock history for this facility
        $obatIdsFromRiwayat = $this->getObatIdsFromRiwayat(
            RiwayatStok::query()->when(
                is_null($fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
            ),
            $tahun,
            $bulan,
        );

        // Also include obat_ids that currently have stock
        $obatIdsFromStok = is_null($fasilitasId)
            ? collect()
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

        DB::transaction(function () use ($laporan, $fasilitasId, $tahun, $bulan, $obatIds) {
            $laporan->details()->delete();

            $details = [];

            foreach ($obatIds as $obatId) {
                $details[] = $this->calculateDetail(
                    lplpoId: $laporan->id,
                    obatId: $obatId,
                    fasilitasId: $fasilitasId,
                    tahun: $tahun,
                    bulan: $bulan,
                );
            }

            DetailLplpo::insert($details);
        });
    }

    protected function queryRiwayatStok(
        ?int $fasilitasId,
        int $obatId,
        int $tahun,
        ?int $bulan = null,
    ): Builder {
        return RiwayatStok::where('obat_id', $obatId)
            ->when(
                is_null($fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
            );
    }

    protected function getPeriodeLabel(?int $fasilitasId, int $tahun, ?int $bulan = null): string
    {
        if ($bulan !== null) {
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            return "{$namaBulan[$bulan]} {$tahun}";
        }

        return "Tahun {$tahun}";
    }

    /**
     * Get LPLPO detail data without writing to DB.
     */
    public function previewData(int $fasilitasId, int $tahun, int $bulan): array
    {
        $obatIdsFromRiwayat = $this->getObatIdsFromRiwayat(
            RiwayatStok::query()->when(
                is_null($fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
            ),
            $tahun,
            $bulan,
        );

        $obatIdsFromStok = is_null($fasilitasId)
            ? collect()
            : StokFaskes::where('fasilitas_id', $fasilitasId)
                ->where('jumlah', '>', 0)
                ->pluck('obat_id');

        $obatIds = $obatIdsFromRiwayat
            ->merge($obatIdsFromStok)
            ->unique()
            ->values();

        if ($obatIds->isEmpty()) {
            return [];
        }

        $details = [];

        foreach ($obatIds as $obatId) {
            $details[] = $this->calculateValues(
                obatId: $obatId,
                fasilitasId: $fasilitasId,
                tahun: $tahun,
                bulan: $bulan,
            );
        }

        return $details;
    }

    private function calculateValues(
        int $obatId,
        ?int $fasilitasId,
        int $tahun,
        int $bulan,
    ): array {
        $baseQuery = $this->queryRiwayatStok($fasilitasId, $obatId, $tahun, $bulan);

        $stokAwalRecord = (clone $baseQuery)
            ->where(function ($q) use ($tahun, $bulan) {
                $q->where('tanggal', '<', sprintf('%04d-%02d-01', $tahun, $bulan));
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $stokAwal = $stokAwalRecord?->stok_sesudah ?? 0;

        $jumlahMasuk = (clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereIn('tipe', ['masuk', 'distribusi_masuk'])
            ->sum(DB::raw('ABS(jumlah)'));

        $jumlahKeluar = (clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'hilang', 'expired'])
            ->sum(DB::raw('ABS(jumlah)'));

        $sisaStok = max(0, $stokAwal + $jumlahMasuk - $jumlahKeluar);

        $stokOptimum = $this->hitungStokOptimum($jumlahKeluar, 1);
        $permintaan = $this->hitungPermintaan($jumlahKeluar, $sisaStok, 1);

        return [
            'obat_id' => $obatId,
            'stok_awal' => $stokAwal,
            'jumlah_masuk' => $jumlahMasuk,
            'jumlah_keluar' => $jumlahKeluar,
            'sisa_stok' => $sisaStok,
            'stok_optimum' => $stokOptimum,
            'permintaan_selanjutnya' => $permintaan,
        ];
    }

    private function calculateDetail(
        int $lplpoId,
        int $obatId,
        ?int $fasilitasId,
        int $tahun,
        int $bulan,
    ): array {
        return array_merge(
            $this->calculateValues($obatId, $fasilitasId, $tahun, $bulan),
            [
                'lplpo_id' => $lplpoId,
                'sudah_diminta' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Validate LPLPO detail data for formula consistency.
     * Returns array with 'errors' and 'warnings' keys.
     */
    public function validate(LaporanLplpo $laporan): array
    {
        $errors = [];
        $warnings = [];

        $laporan->loadMissing('details.obat');

        foreach ($laporan->details as $detail) {
            $namaObat = $detail->obat?->nama_obat ?? 'ID #'.$detail->obat_id;

            // Cek persediaan = stok_awal + jumlah_masuk
            $expectedPersediaan = $detail->stok_awal + $detail->jumlah_masuk;
            if ($detail->jumlah_masuk > 0 && $expectedPersediaan !== ($detail->stok_awal + $detail->jumlah_masuk)) {
                $errors[] = "Persediaan {$namaObat} tidak konsisten (expected {$expectedPersediaan})";
            }

            // Cek sisa_stok = persediaan - jumlah_keluar
            $expectedSisaStok = $expectedPersediaan - $detail->jumlah_keluar;
            $actualSisaStok = max(0, $expectedSisaStok);
            if ($detail->sisa_stok !== $actualSisaStok) {
                $errors[] = "Sisa stok {$namaObat} tidak konsisten (expected {$actualSisaStok}, got {$detail->sisa_stok})";
            }

            // Warning: selisih dengan StokFaskes saat ini
            if ($laporan->fasilitas_id) {
                $stokFaskes = StokFaskes::where('fasilitas_id', $laporan->fasilitas_id)
                    ->where('obat_id', $detail->obat_id)
                    ->first();

                if ($stokFaskes && $detail->sisa_stok > 0) {
                    $selisih = abs($stokFaskes->jumlah - $detail->sisa_stok);
                    $persentase = $detail->sisa_stok > 0 ? ($selisih / $detail->sisa_stok) * 100 : 0;

                    if ($persentase > 50) {
                        $warnings[] = "Selisih signifikan stok fisik vs LPLPO untuk {$namaObat}: sistem={$stokFaskes->jumlah}, LPLPO={$detail->sisa_stok} (selisih {$persentase}%)";
                    } elseif ($persentase > 10) {
                        $warnings[] = "Selisih stok fisik vs LPLPO untuk {$namaObat}: sistem={$stokFaskes->jumlah}, LPLPO={$detail->sisa_stok} (selisih {$persentase}%)";
                    }
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Compare LPLPO sisa_stok with current StokFaskes for each detail item.
     * Returns array of comparison results.
     */
    public function getStokFaskesComparison(LaporanLplpo $laporan): array
    {
        $comparison = [];

        $laporan->loadMissing('details.obat');

        foreach ($laporan->details as $detail) {
            $stokFaskes = StokFaskes::where('fasilitas_id', $laporan->fasilitas_id)
                ->where('obat_id', $detail->obat_id)
                ->first();

            $stokSistem = $stokFaskes?->jumlah ?? 0;
            $stokLplpo = $detail->sisa_stok;
            $selisih = $stokSistem - $stokLplpo;

            $comparison[] = [
                'obat_id' => $detail->obat_id,
                'nama_obat' => $detail->obat?->nama_obat ?? '-',
                'stok_lplpo' => $stokLplpo,
                'stok_sistem' => $stokSistem,
                'selisih' => $selisih,
                'status' => match (true) {
                    $stokSistem === $stokLplpo => 'sesuai',
                    abs($selisih) <= ($stokLplpo * 0.1) => 'minor',
                    abs($selisih) <= ($stokLplpo * 0.5) => 'moderate',
                    default => 'signifikan',
                },
            ];
        }

        return $comparison;
    }
}
