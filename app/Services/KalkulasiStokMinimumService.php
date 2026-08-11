<?php

namespace App\Services;

use App\Models\DetailDistribusiObat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use Illuminate\Support\Facades\DB;

class KalkulasiStokMinimumService
{
    private const SAFETY_FACTOR_GUDANG = 2;

    private const SAFETY_FACTOR_FASKES = 1.5;

    private const PERIODE_BULAN = 6;

    private const MINIMUM_STOK = 10;

    /**
     * Hitung ulang stok_minimum untuk gudang dan semua faskes.
     *
     * @return array<string, int> ['gudang' => count, 'faskes' => count]
     */
    public function kalkulasiSemua(): array
    {
        return [
            'gudang' => $this->kalkulasiGudang(),
            'faskes' => $this->kalkulasiFaskes(),
        ];
    }

    /**
     * Hitung stok_minimum untuk Gudang Dinas berdasarkan
     * rata-rata distribusi ke puskesmas 6 bulan terakhir × safety factor.
     */
    public function kalkulasiGudang(): int
    {
        $batas = now()->subMonths(self::PERIODE_BULAN);

        $distribusiPerObat = DetailDistribusiObat::query()
            ->whereHas('distribusi', fn ($q) => $q
                ->where('tipe_distribusi', 'dinas_ke_puskesmas')
                ->where('status', 'diterima')
                ->where('tanggal_kirim', '>=', $batas)
            )
            ->selectRaw('obat_id, SUM(jumlah) as total')
            ->groupBy('obat_id')
            ->pluck('total', 'obat_id');

        $updated = 0;
        foreach ($distribusiPerObat as $obatId => $total) {
            $rataRata = $total / self::PERIODE_BULAN;
            $stokMinimum = max(self::MINIMUM_STOK, (int) round($rataRata * self::SAFETY_FACTOR_GUDANG));

            StokGudang::where('obat_id', $obatId)
                ->where('jumlah', '>=', 0) // always true, just to chain
                ->update(['stok_minimum' => $stokMinimum]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Hitung stok_minimum untuk setiap Faskes (Puskesmas/Pustu) berdasarkan
     * rata-rata pemakaian 6 bulan terakhir per fasilitas × safety factor.
     *
     * Reads from detail_pemakaian_obat joined with pemakaian_obat header.
     */
    public function kalkulasiFaskes(): int
    {
        $batas = now()->subMonths(self::PERIODE_BULAN);

        $pemakaianPerFaskes = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->where('p.tanggal_pemakaian', '>=', $batas)
            ->selectRaw('p.fasilitas_id as fasilitas_id, d.obat_id as obat_id, SUM(d.jumlah) as total')
            ->groupBy('p.fasilitas_id', 'd.obat_id')
            ->get();

        $updated = 0;
        foreach ($pemakaianPerFaskes as $row) {
            $rataRata = $row->total / self::PERIODE_BULAN;
            $stokMinimum = max(self::MINIMUM_STOK, (int) round($rataRata * self::SAFETY_FACTOR_FASKES));

            StokFaskes::where('fasilitas_id', $row->fasilitas_id)
                ->where('obat_id', $row->obat_id)
                ->update(['stok_minimum' => $stokMinimum]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Dapatkan ringkasan untuk logging.
     */
    public function getRingkasan(): array
    {
        return [
            'gudang' => [
                'total_obat' => StokGudang::count(),
                'dengan_data' => $this->getGudangDenganDataCount(),
                'safety_factor' => self::SAFETY_FACTOR_GUDANG,
                'periode_bulan' => self::PERIODE_BULAN,
            ],
            'faskes' => [
                'total_faskes' => StokFaskes::select('fasilitas_id')->distinct()->count(),
                'total_record' => StokFaskes::count(),
                'safety_factor' => self::SAFETY_FACTOR_FASKES,
                'periode_bulan' => self::PERIODE_BULAN,
            ],
        ];
    }

    private function getGudangDenganDataCount(): int
    {
        $batas = now()->subMonths(self::PERIODE_BULAN);

        return DetailDistribusiObat::query()
            ->whereHas('distribusi', fn ($q) => $q
                ->where('tipe_distribusi', 'dinas_ke_puskesmas')
                ->where('status', 'diterima')
                ->where('tanggal_kirim', '>=', $batas)
            )
            ->distinct('obat_id')
            ->count('obat_id');
    }
}
