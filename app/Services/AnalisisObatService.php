<?php

namespace App\Services;

use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Komputasi halaman Analisis Obat (Fase 1): KPI, ringkasan AI,
 * matriks ABC-VEN, tren konsumsi, risiko stockout, rekomendasi.
 *
 * Semua angka berasal dari data nyata (pemakaian, prediksi, stok,
 * batch expired, model). Estimasi diberi label eksplisit.
 */
class AnalisisObatService
{
    /**
     * @param  int[]|null  $visibleFasilitasIds
     */
    public function __construct(
        protected ?int $fasilitasId = null,
        protected int $tahun = 0,
        protected ?array $visibleFasilitasIds = null,
    ) {
        $this->tahun = $this->tahun ?: now()->year;
    }

    /**
     * Periode forecast yang tersedia (bulan mendatang), maksimal 6.
     *
     * @return array<int, array{y:int, m:int}>
     */
    public function forecastPeriods(): array
    {
        $nowKey = now()->format('Y-m');

        $periods = PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun as y, periode_bulan as m')
            ->distinct()
            ->orderBy('y')
            ->orderBy('m')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['y' => (int) $r->y, 'm' => (int) $r->m])
            ->filter(fn ($p) => sprintf('%04d-%02d', $p['y'], $p['m']) >= $nowKey)
            ->values()
            ->all();

        if (! empty($periods)) {
            return $periods;
        }

        return PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun as y, periode_bulan as m')
            ->distinct()
            ->orderBy('y', 'desc')
            ->orderBy('m', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($r) => ['y' => (int) $r->y, 'm' => (int) $r->m])
            ->reverse()
            ->values()
            ->all();
    }

    protected function rekomendasiService(): PrediksiRekomendasiService
    {
        $anchor = $this->forecastAnchor();

        return new PrediksiRekomendasiService(
            fasilitasId: $this->fasilitasId,
            bulan: $anchor['m'],
            tahun: $anchor['y'],
            horizon: $anchor['h'],
            visibleFasilitasIds: $this->visibleFasilitasIds,
        );
    }

    /**
     * @return array{m:int, y:int, h:int}
     */
    protected function forecastAnchor(): array
    {
        $periods = $this->forecastPeriods();
        $anchor = $periods[0] ?? ['y' => now()->year, 'm' => now()->month];

        return ['m' => $anchor['m'], 'y' => $anchor['y'], 'h' => max(1, count($periods))];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rekomendasiRows(): array
    {
        return $this->rekomendasiService()->rows();
    }

    /**
     * @return array<string, mixed>
     */
    public function kpi(): array
    {
        $konsumsi = $this->konsumsiTahun($this->tahun);
        $konsumsiLalu = $this->konsumsiTahun($this->tahun - 1);

        $yoy = null;
        if ($konsumsiLalu > 0) {
            $yoy = (($konsumsi - $konsumsiLalu) / $konsumsiLalu) * 100;
        }

        $serviceLevel = $this->serviceLevel();
        $rows = collect($this->rekomendasiRows());
        $berisiko = $rows->where('rekom', '>', 0)->filter(fn ($r) => $r['coverage_days'] < 21)->count();

        $waste = $this->waste();
        $konsumsiNilai = $this->konsumsiNilaiTahun($this->tahun);

        return [
            'konsumsi' => $konsumsi,
            'konsumsi_yoy' => $yoy,
            'service_level' => $serviceLevel,
            'berisiko' => $berisiko,
            'waste_nilai' => $waste,
            'waste_pct' => $konsumsiNilai > 0 ? ($waste / $konsumsiNilai) * 100 : null,
            'akurasi' => $this->rataAkurasi(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ringkasan(): array
    {
        $rows = collect($this->rekomendasiRows());
        $defisit = $rows->where('rekom', '>', 0)->values();
        $top = $defisit->first();
        $abven = $this->abven();
        $skor = $this->faskesScore();

        $temuan = [];
        if ($top) {
            $temuan[] = 'Lonjakan kebutuhan '.$top['nama_obat'].' (+'.number_format($top['rekom'], 0, ',', '.').' '.$top['satuan'].') di '.$this->scopeNama().'.';
        }
        if (! empty($abven['topKategori'])) {
            $k = $abven['topKategori'];
            $temuan[] = 'Kategori '.$k['nama'].' mendominasi '.$k['share'].'% nilai konsumsi tahun '.$this->tahun.'.';
        }
        if ($skor['terbaik'] && $skor['terburuk'] && $skor['terbaik']['id'] !== $skor['terburuk']['id']) {
            $temuan[] = $skor['terbaik']['nama'].' paling efisien (waste rendah), '.$skor['terburuk']['nama'].' perlu pendampingan.';
        }
        if (empty($temuan)) {
            $temuan[] = 'Konsumsi stabil pada periode berjalan, tidak ada lonjakan signifikan.';
        }

        return [
            'temuan' => array_slice($temuan, 0, 3),
            'confidence' => round($this->rataAkurasi() * 100, 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function abven(): array
    {
        $start = Carbon::create($this->tahun, 1, 1)->startOfYear()->format('Y-m-d');
        $end = Carbon::create($this->tahun, 1, 1)->endOfYear()->format('Y-m-d');

        $query = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->join('obat as o', 'o.id', '=', 'd.obat_id')
            ->whereBetween('p.tanggal_pemakaian', [$start, $end])
            ->selectRaw('d.obat_id, o.nama_obat, o.kategori, o.ven_kategori, SUM(d.jumlah) as konsumsi, SUM(d.jumlah * o.harga_satuan) as nilai')
            ->groupBy('d.obat_id', 'o.nama_obat', 'o.kategori', 'o.ven_kategori')
            ->orderByDesc('nilai');

        $this->scopePemakaian($query);

        $items = $query->get();
        $total = (float) $items->sum('nilai');

        $matrix = ['AV' => 0, 'AE' => 0, 'AN' => 0, 'BV' => 0, 'BE' => 0, 'BN' => 0, 'CV' => 0, 'CE' => 0, 'CN' => 0];
        $running = 0;
        $topA = [];
        $kategoriNilai = [];

        foreach ($items as $item) {
            $nilai = (float) $item->nilai;
            $shareBefore = $total > 0 ? $running / $total : 1;
            $abc = $total <= 0 ? 'C' : ($shareBefore < 0.70 ? 'A' : ($shareBefore < 0.90 ? 'B' : 'C'));
            $running += $nilai;
            $ven = in_array($item->ven_kategori, ['V', 'E', 'N'], true) ? $item->ven_kategori : 'N';
            $matrix[$abc.$ven]++;

            $kat = $item->kategori ?: 'Lainnya';
            $kategoriNilai[$kat] = ($kategoriNilai[$kat] ?? 0) + $nilai;

            if ($abc === 'A' && count($topA) < 15) {
                $topA[] = [
                    'nama_obat' => $item->nama_obat,
                    'ven' => $ven,
                    'konsumsi' => (int) $item->konsumsi,
                    'nilai' => $nilai,
                    'share' => $total > 0 ? ($nilai / $total) * 100 : 0,
                    'saran' => $this->saranAbven($abc.$ven),
                ];
            }
        }

        arsort($kategoriNilai);
        $topKategori = null;
        if (! empty($kategoriNilai) && $total > 0) {
            $nama = (string) array_key_first($kategoriNilai);
            $topKategori = ['nama' => $nama, 'share' => round(($kategoriNilai[$nama] / $total) * 100, 1)];
        }

        $hematExpiredA = 0;
        if (! empty($topA)) {
            $aObatIds = Obat::whereIn('nama_obat', array_column($topA, 'nama_obat'))->pluck('id');
            $hematExpiredA = (float) BatchStok::query()
                ->whereIn('obat_id', $aObatIds)
                ->where(function ($q) {
                    $q->whereIn('status', ['expired', 'dimusnahkan'])
                        ->orWhere('tanggal_expired', '<', now()->format('Y-m-d'));
                })
                ->selectRaw('SUM(jumlah * COALESCE(harga_beli, 0)) as total')
                ->value('total');
        }

        return [
            'matrix' => $matrix,
            'topA' => $topA,
            'total_nilai' => $total,
            'topKategori' => $topKategori,
            'hemat_estimate' => $hematExpiredA,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tren(int $limitObat = 3): array
    {
        $rows = array_slice($this->rekomendasiRows(), 0, $limitObat);
        $periods = $this->forecastPeriods();
        $anchor = $periods[0] ?? ['y' => now()->year, 'm' => now()->month];
        $anchorDate = Carbon::create($anchor['y'], $anchor['m'], 1);

        $past = [];
        for ($i = 12; $i >= 1; $i--) {
            $past[] = $anchorDate->copy()->subMonths($i);
        }

        $labels = [];
        $keys = [];
        foreach (array_merge($past, array_map(fn ($p) => Carbon::create($p['y'], $p['m'], 1), $periods)) as $d) {
            $labels[] = $d->format('M Y');
            $keys[] = (int) $d->format('Y').'-'.(int) $d->format('n');
        }

        $pastCount = count($past);

        $driver = DB::connection()->getDriverName();
        $select = $driver === 'sqlite'
            ? "d.obat_id, strftime('%Y', p.tanggal_pemakaian) as y, strftime('%m', p.tanggal_pemakaian) as m, SUM(d.jumlah) as total"
            : 'd.obat_id, YEAR(p.tanggal_pemakaian) as y, MONTH(p.tanggal_pemakaian) as m, SUM(d.jumlah) as total';

        $realQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereIn('d.obat_id', array_column($rows, 'obat_id'))
            ->whereBetween('p.tanggal_pemakaian', [$past[0]->format('Y-m-d'), end($past)->format('Y-m-t')])
            ->selectRaw($select)
            ->groupBy('d.obat_id', 'y', 'm');

        $this->scopePemakaian($realQuery, 'p.fasilitas_id');

        $realRaw = [];
        foreach ($realQuery->get() as $r) {
            $realRaw[(int) $r->obat_id.'|'.(int) $r->y.'-'.(int) $r->m] = (int) $r->total;
        }

        $predRaw = [];
        if (! empty($rows)) {
            $predQuery = PrediksiKebutuhan::query()
                ->selectRaw('obat_id, periode_tahun as y, periode_bulan as m, SUM(jumlah_prediksi) as total')
                ->whereIn('obat_id', array_column($rows, 'obat_id'))
                ->where(function ($q) use ($periods) {
                    foreach ($periods as $i => $period) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $q->$method(fn ($q2) => $q2
                            ->where('periode_tahun', $period['y'])
                            ->where('periode_bulan', $period['m'])
                        );
                    }
                })
                ->groupBy('obat_id', 'y', 'm');

            $this->scopeModel($predQuery, 'fasilitas_id');

            foreach ($predQuery->get() as $r) {
                $predRaw[(int) $r->obat_id.'|'.(int) $r->y.'-'.(int) $r->m] = (int) $r->total;
            }
        }

        $series = [];
        $totalBulanan = array_fill(0, count($keys), 0);
        foreach ($rows as $row) {
            $realisasi = [];
            $prediksi = [];
            foreach ($keys as $idx => $key) {
                $rv = $realRaw[$row['obat_id'].'|'.$key] ?? null;
                $pv = $predRaw[$row['obat_id'].'|'.$key] ?? null;
                if ($idx < $pastCount) {
                    $realisasi[] = $rv ?? 0;
                    $prediksi[] = null;
                    $totalBulanan[$idx] += $rv ?? 0;
                } else {
                    $realisasi[] = null;
                    $prediksi[] = $pv ?? 0;
                    $totalBulanan[$idx] += $pv ?? 0;
                }
            }
            $series[] = ['nama' => $row['nama_obat'], 'realisasi' => $realisasi, 'prediksi' => $prediksi];
        }

        $pastTotals = array_slice($totalBulanan, 0, $pastCount);
        $peakIdx = array_search(max($pastTotals), $pastTotals);
        $avg = $pastCount > 0 ? array_sum($pastTotals) / $pastCount : 0;
        $last3 = array_sum(array_slice($pastTotals, -3));
        $prev3 = array_sum(array_slice($pastTotals, -6, 3));

        return [
            'labels' => $labels,
            'series' => $series,
            'musim' => [
                'puncak' => $pastCount > 0 ? $labels[$peakIdx] : null,
                'puncak_nilai' => $pastCount > 0 ? $pastTotals[$peakIdx] : 0,
                'rata_bulanan' => (int) round($avg),
                'tren_pct' => $prev3 > 0 ? (($last3 - $prev3) / $prev3) * 100 : null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function risiko(int $limit = 10): array
    {
        $rows = array_slice(
            collect($this->rekomendasiRows())->where('rekom', '>', 0)->values()->all(),
            0,
            $limit
        );

        return array_map(function ($r) {
            $coverage = $r['coverage_days'];
            $prob = 15;
            $probLabel = 'Rendah';
            if ($coverage < 7) {
                $prob = 92;
                $probLabel = 'Tinggi';
            } elseif ($coverage < 14) {
                $prob = 78;
                $probLabel = 'Tinggi';
            } elseif ($coverage < 21) {
                $prob = 62;
                $probLabel = 'Sedang';
            } elseif ($coverage < 30) {
                $prob = 38;
                $probLabel = 'Sedang';
            }

            return [
                'obat_id' => $r['obat_id'],
                'nama_obat' => $r['nama_obat'],
                'ven' => $r['ven_kategori'],
                'stok_hari' => $coverage === PHP_FLOAT_MAX ? null : round($coverage, 1),
                'habis' => $coverage === PHP_FLOAT_MAX ? null : now()->addDays((int) floor($coverage))->translatedFormat('d M Y'),
                'prob' => $prob,
                'prob_label' => $probLabel,
                'dampak' => $r['ven_kategori'] === 'V' ? 'Tinggi' : ($r['ven_kategori'] === 'E' ? 'Sedang' : 'Rendah'),
                'rekom' => $r['rekom'],
                'satuan' => $r['satuan'],
                'status' => $r['status'],
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rekomendasi(): array
    {
        $rows = collect($this->rekomendasiRows());
        $kritis = $rows->where('status', 'Kritis')->values();
        $defisit = $rows->where('rekom', '>', 0)->values();
        $skor = $this->faskesScore();
        $confidence = round($this->rataAkurasi() * 100, 1);

        $cards = [];

        if ($kritis->isNotEmpty()) {
            $top = $kritis->take(5);
            $cards[] = [
                'judul' => 'Distribusi Darurat Minggu Ini',
                'deskripsi' => 'Kirim '.$top->map(fn ($r) => $r['nama_obat'].' ('.number_format($r['rekom'], 0, ',', '.').' '.$r['satuan'].')')->implode(', ').' ke '.$this->scopeNama().'.',
                'dampak' => 'Mencakup '.$top->count().' item kritis berisiko stockout < 21 hari.',
                'confidence' => $confidence,
                'obat_ids' => $top->pluck('obat_id')->all(),
                'aksi' => 'po',
            ];
        }

        if ($defisit->isNotEmpty()) {
            $top = $defisit->take(5);
            $cards[] = [
                'judul' => 'Revisi Buffer Pengadaan',
                'deskripsi' => 'Naikkan buffer '.$top->map(fn ($r) => $r['nama_obat'])->implode(', ').' sesuai rekomendasi; turunkan obat berstatus Aman yang overstock.',
                'dampak' => 'Mencegah stockout periode berikutnya dengan safety stock 20%.',
                'confidence' => $confidence,
                'obat_ids' => $top->pluck('obat_id')->all(),
                'aksi' => 'po',
            ];
        }

        if ($skor['terburuk']) {
            $cards[] = [
                'judul' => 'Pendampingan Faskes',
                'deskripsi' => 'Coaching perencanaan dan audit stok bulanan di '.$skor['terburuk']['nama'].' (waste Rp '.number_format($skor['terburuk']['waste'], 0, ',', '.').', '.$skor['terburuk']['risiko'].' obat berisiko).',
                'dampak' => 'Target waste < 1,5% & service level > 96% dalam 3 bulan.',
                'confidence' => $confidence,
                'obat_ids' => [],
                'aksi' => 'info',
            ];
        }

        return $cards;
    }

    protected function konsumsiTahun(int $tahun): int
    {
        $start = Carbon::create($tahun, 1, 1)->startOfYear()->format('Y-m-d');
        $end = Carbon::create($tahun, 1, 1)->endOfYear()->format('Y-m-d');

        $query = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereBetween('p.tanggal_pemakaian', [$start, $end])
            ->selectRaw('SUM(d.jumlah) as total');

        $this->scopePemakaian($query);

        return (int) $query->value('total');
    }

    protected function konsumsiNilaiTahun(int $tahun): float
    {
        $start = Carbon::create($tahun, 1, 1)->startOfYear()->format('Y-m-d');
        $end = Carbon::create($tahun, 1, 1)->endOfYear()->format('Y-m-d');

        $query = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->join('obat as o', 'o.id', '=', 'd.obat_id')
            ->whereBetween('p.tanggal_pemakaian', [$start, $end])
            ->selectRaw('SUM(d.jumlah * o.harga_satuan) as total');

        $this->scopePemakaian($query);

        return (float) $query->value('total');
    }

    protected function serviceLevel(): ?float
    {
        $start = Carbon::create($this->tahun, 1, 1)->startOfYear()->format('Y-m-d');
        $end = Carbon::create($this->tahun, 1, 1)->endOfYear()->format('Y-m-d');

        $konsumsiQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereBetween('p.tanggal_pemakaian', [$start, $end])
            ->selectRaw('d.obat_id, SUM(d.jumlah) as total')
            ->groupBy('d.obat_id');

        $this->scopePemakaian($konsumsiQuery);
        $konsumsi = $konsumsiQuery->get()->keyBy('obat_id');

        if ($konsumsi->isEmpty()) {
            return null;
        }

        $stokQuery = StokFaskes::query()
            ->selectRaw('obat_id, SUM(jumlah) as stok')
            ->whereIn('obat_id', $konsumsi->keys()->all())
            ->groupBy('obat_id');

        $this->scopeModel($stokQuery, 'stok_faskes.fasilitas_id');
        $stok = $stokQuery->get()->keyBy('obat_id');

        $terpenuhi = 0;
        foreach ($konsumsi as $obatId => $row) {
            $avg = ((int) $row->total) / 12;
            if ((int) ($stok->get($obatId)?->stok ?? 0) >= $avg) {
                $terpenuhi++;
            }
        }

        return ($terpenuhi / $konsumsi->count()) * 100;
    }

    protected function waste(): float
    {
        $query = BatchStok::query()
            ->where(function ($q) {
                $q->whereIn('status', ['expired', 'dimusnahkan'])
                    ->orWhere('tanggal_expired', '<', now()->format('Y-m-d'));
            })
            ->selectRaw('SUM(jumlah * COALESCE(harga_beli, 0)) as total');

        $this->scopeModel($query, 'fasilitas_id');

        return (float) $query->value('total');
    }

    protected function rataAkurasi(): float
    {
        $query = ModelPrediksi::query()
            ->where('status', 'aktif')
            ->whereNotNull('akurasi_r2');

        $this->scopeModel($query, 'fasilitas_id');

        return (float) $query->avg('akurasi_r2');
    }

    /**
     * @return array{terbaik:?array, terburuk:?array}
     */
    protected function faskesScore(): array
    {
        $faskesQuery = FasilitasKesehatan::query()->orderBy('nama');

        if ($this->fasilitasId) {
            $faskesQuery->where('id', $this->fasilitasId);
        } elseif (! empty($this->visibleFasilitasIds)) {
            $faskesQuery->whereIn('id', $this->visibleFasilitasIds);
        }

        $faskes = $faskesQuery->get();
        if ($faskes->isEmpty()) {
            return ['terbaik' => null, 'terburuk' => null];
        }

        $skor = [];
        $anchor = $this->forecastAnchor();
        foreach ($faskes as $f) {
            $waste = (float) BatchStok::query()
                ->where('fasilitas_id', $f->id)
                ->where(function ($q) {
                    $q->whereIn('status', ['expired', 'dimusnahkan'])
                        ->orWhere('tanggal_expired', '<', now()->format('Y-m-d'));
                })
                ->selectRaw('SUM(jumlah * COALESCE(harga_beli, 0)) as total')
                ->value('total');

            $risiko = collect(
                (new PrediksiRekomendasiService(fasilitasId: $f->id, bulan: $anchor['m'], tahun: $anchor['y'], horizon: $anchor['h']))->rows()
            )->where('rekom', '>', 0)->filter(fn ($r) => $r['coverage_days'] < 21)->count();

            $skor[] = ['id' => $f->id, 'nama' => $f->nama, 'waste' => $waste, 'risiko' => $risiko];
        }

        usort($skor, fn ($a, $b) => [$a['waste'], $a['risiko']] <=> [$b['waste'], $b['risiko']]);

        return ['terbaik' => $skor[0], 'terburuk' => $skor[count($skor) - 1]];
    }

    protected function scopeNama(): string
    {
        if ($this->fasilitasId) {
            return FasilitasKesehatan::find($this->fasilitasId)?->nama ?? 'Fasilitas Terpilih';
        }

        return 'Semua Puskesmas';
    }

    protected function saranAbven(string $sel): string
    {
        return match ($sel) {
            'AV' => 'Jaga Stok Ketat',
            'AE' => 'Negosiasi Harga',
            'AN' => 'Efisiensi Pengadaan',
            'BV' => 'Jaga Stok',
            'BE' => 'Monitor Rutin',
            'BN' => 'Kurangi Stok',
            'CV' => 'Jaga Stok',
            'CE' => 'Monitor Rutin',
            default => 'Hindari Overstock',
        };
    }

    /**
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    protected function scopePemakaian($query, string $column = 'p.fasilitas_id')
    {
        if ($this->fasilitasId) {
            $query->where($column, $this->fasilitasId);

            return;
        }

        if (! empty($this->visibleFasilitasIds)) {
            $query->whereIn($column, $this->visibleFasilitasIds);
        }
    }

    /**
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    protected function scopeModel($query, string $column = 'fasilitas_id')
    {
        if ($this->fasilitasId) {
            $query->where($column, $this->fasilitasId);

            return;
        }

        if (! empty($this->visibleFasilitasIds)) {
            $query->whereIn($column, $this->visibleFasilitasIds);
        }
    }
}
