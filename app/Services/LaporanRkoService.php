<?php

namespace App\Services;

use App\Models\Obat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Services\Laporan\LaporanBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LaporanRkoService extends LaporanBaseService
{
    /**
     * Get RKO preview data from stock history (12 months of previous year).
     */
    public function previewData(int $fasilitasId, int $periodeRkoTahun): array
    {
        // RKO untuk tahun X → hitung pemakaian dari tahun (X-1)
        $tahunPemakaian = $periodeRkoTahun - 1;

        $obatIdsFromRiwayat = $this->getObatIdsFromRiwayat(
            RiwayatStok::query()->where('fasilitas_id', $fasilitasId),
            $tahunPemakaian,
        );

        $obatIdsFromStok = StokFaskes::where('fasilitas_id', $fasilitasId)
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
            $calculated = $this->calculateValues(
                obatId: $obatId,
                fasilitasId: $fasilitasId,
                tahunPemakaian: $tahunPemakaian,
            );

            if ($calculated !== null) {
                $details[] = $calculated;
            }
        }

        return $details;
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
        return "Tahun {$tahun}";
    }

    /**
     * Calculate RKO values for single obat based on previous year usage.
     */
    private function calculateValues(
        int $obatId,
        int $fasilitasId,
        int $tahunPemakaian,
    ): ?array {
        $obat = Obat::find($obatId);

        if (! $obat || $obat->status !== 'aktif') {
            return null;
        }

        $baseQuery = $this->queryRiwayatStok($fasilitasId, $obatId, $tahunPemakaian);

        // Pemakaian tahun sebelumnya (keluar) dari 12 bulan
        $pemakaianTahunSebelumnya = (clone $baseQuery)
            ->whereYear('tanggal', $tahunPemakaian)
            ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'hilang', 'expired'])
            ->sum(DB::raw('ABS(jumlah)'));

        // Rata-rata pemakaian per bulan
        $rataRataPemakaianBulanan = (int) round($pemakaianTahunSebelumnya / 12);

        // Stok akhir dari StokFaskes terkini
        $stokAkhir = StokFaskes::where('fasilitas_id', $fasilitasId)
            ->where('obat_id', $obatId)
            ->value('jumlah') ?? 0;

        // Harga perkiraan dari master obat, fallback ke rata-rata batch
        $hargaPerkiraan = (float) ($obat->harga_satuan
            ?? $obat->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
            ?? 0);

        // VEN kategori
        $venKategori = $obat->ven_kategori;

        // Hitung rumus Kemenkes
        $kebutuhanTahunan = $rataRataPemakaianBulanan * 18;
        $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);

        $bufferPersen = $this->getBufferPersenByVen($venKategori);
        $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
        $totalKebutuhan = $rencanaKebutuhan + $bufferQty;

        $usulan = $totalKebutuhan;
        $totalHarga = $usulan * $hargaPerkiraan;

        return [
            'obat_id' => $obatId,
            'pemakaian_tahun_sebelumnya' => $pemakaianTahunSebelumnya,
            'rata_rata_pemakaian_bulanan' => $rataRataPemakaianBulanan,
            'stok_akhir' => $stokAkhir,
            'kebutuhan_tahunan' => $kebutuhanTahunan,
            'rencana_kebutuhan' => $rencanaKebutuhan,
            'usulan' => $usulan,
            'buffer_stock_persen' => $bufferPersen,
            'buffer_stok_qty' => $bufferQty,
            'total_kebutuhan' => $totalKebutuhan,
            'harga_perkiraan' => $hargaPerkiraan,
            'total_harga' => $totalHarga,
            'ven_kategori_hidden' => $venKategori,
            'abc_kategori' => null,
            'keterangan' => null,
        ];
    }

    private function getBufferPersenByVen(?string $ven): float
    {
        return match ($ven) {
            'V' => 30,
            'E' => 20,
            'N' => 10,
            default => 15,
        };
    }
}
