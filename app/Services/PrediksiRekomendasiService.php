<?php

namespace App\Services;

use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung rekomendasi pengadaan obat berbasis hasil prediksi AI.
 *
 * Rekomendasi = (total prediksi horizon + safety stock 20%) - stok saat ini.
 * Baris dikelompokkan per obat (agregasi lintas fasilitas bila tidak memilih satu faskes).
 */
class PrediksiRekomendasiService
{
    public const SAFETY_STOCK_RATE = 0.20;

    /**
     * @param  int[]|null  $visibleFasilitasIds
     */
    public function __construct(
        protected ?int $fasilitasId = null,
        protected int $bulan = 0,
        protected int $tahun = 0,
        protected int $horizon = 3,
        protected ?string $kategori = null,
        protected ?string $cari = null,
        protected ?array $visibleFasilitasIds = null,
    ) {
        $this->bulan = $this->bulan ?: now()->month;
        $this->tahun = $this->tahun ?: now()->year;
        $this->horizon = max(1, $this->horizon);
    }

    /**
     * Daftar periode (tahun, bulan) yang dicakup horizon, mulai dari bulan anchor.
     *
     * @return array<int, array{y:int, m:int}>
     */
    public function periods(): array
    {
        $periods = [];
        $date = Carbon::create($this->tahun, $this->bulan, 1);

        for ($i = 0; $i < $this->horizon; $i++) {
            $periods[] = ['y' => (int) $date->format('Y'), 'm' => (int) $date->format('n')];
            $date = $date->copy()->addMonth();
        }

        return $periods;
    }

    /**
     * Baris rekomendasi per obat, dilengkapi status & confidence.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $periods = $this->periods();

        $prediksiQuery = PrediksiKebutuhan::query()
            ->selectRaw('prediksi_kebutuhan.obat_id, SUM(jumlah_prediksi) as prediksi_total, COUNT(*) as bulan_count, AVG(model.akurasi_r2) as akurasi, MAX(prediksi_kebutuhan.metode) as metode')
            ->leftJoin('model_prediksi as model', 'model.id', '=', 'prediksi_kebutuhan.model_id')
            ->where(function ($q) use ($periods) {
                foreach ($periods as $i => $period) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method(fn ($q2) => $q2
                        ->where('periode_tahun', $period['y'])
                        ->where('periode_bulan', $period['m'])
                    );
                }
            });

        $prediksiQuery = $this->applyScope($prediksiQuery, 'prediksi_kebutuhan.fasilitas_id');
        $prediksiQuery->groupBy('prediksi_kebutuhan.obat_id');

        $prediksiRaw = $prediksiQuery->get()->keyBy('obat_id');

        $stokQuery = StokFaskes::query()
            ->selectRaw('obat_id, SUM(jumlah) as stok')
            ->groupBy('obat_id');
        $stokQuery = $this->applyScope($stokQuery, 'stok_faskes.fasilitas_id');
        $stokRaw = $stokQuery->get()->keyBy('obat_id');

        $obatIds = $prediksiRaw->keys()->merge($stokRaw->keys())->all();
        if (empty($obatIds)) {
            return [];
        }

        $obats = Obat::query()
            ->whereIn('id', $obatIds)
            ->when($this->kategori, fn ($q, $v) => $q->where('kategori', $v))
            ->when($this->cari, fn ($q, $v) => $q->where('nama_obat', 'like', "%{$v}%"))
            ->get()
            ->keyBy('id');

        $rows = collect();

        foreach ($obats as $obat) {
            $pred = $prediksiRaw->get($obat->id);
            $stok = (int) ($stokRaw->get($obat->id)?->stok ?? 0);
            $prediksiTotal = (int) ($pred?->prediksi_total ?? 0);
            $bulanCount = (int) ($pred?->bulan_count ?? 0);
            $akurasi = $pred?->akurasi !== null ? (float) $pred->akurasi : null;

            $rekom = (int) ceil(($prediksiTotal * (1 + self::SAFETY_STOCK_RATE)) - $stok);
            $rekom = max(0, $rekom);

            $avgMonthly = $bulanCount > 0 ? $prediksiTotal / $bulanCount : 0;
            $coverageDays = $avgMonthly > 0 ? ($stok / $avgMonthly) * 30.44 : PHP_FLOAT_MAX;

            if ($rekom <= 0) {
                $status = 'Aman';
                $statusColor = 'success';
            } elseif ($coverageDays < 21) {
                $status = 'Kritis';
                $statusColor = 'danger';
            } else {
                $status = 'Perlu Pesan';
                $statusColor = 'warning';
            }

            $spikePct = $stok > 0
                ? (($prediksiTotal - $stok) / $stok) * 100
                : ($prediksiTotal > 0 ? 100 : 0);

            $rows[] = [
                'obat_id' => (int) $obat->id,
                'nama_obat' => $obat->nama_obat,
                'kategori' => $obat->kategori,
                'satuan' => $obat->satuan,
                'ven_kategori' => $obat->ven_kategori,
                'harga_satuan' => (float) $obat->harga_satuan,
                'stok' => $stok,
                'rata_per_bulan' => (int) round($avgMonthly),
                'prediksi_horizon' => $prediksiTotal,
                'bulan_count' => $bulanCount,
                'rekom' => $rekom,
                'metode' => $pred?->metode,
                'akurasi' => $akurasi,
                'status' => $status,
                'status_color' => $statusColor,
                'coverage_days' => $coverageDays,
                'spike_pct' => $spikePct,
            ];
        }

        return $rows->sortByDesc('spike_pct')->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function kpi(float $defaultAkurasi = 0): array
    {
        $rows = collect($this->rows());
        $defisit = $rows->where('rekom', '>', 0);
        $totalObatAktif = Obat::where('status', 'aktif')->count();

        $estimasi = $defisit->sum(fn ($r) => $r['rekom'] * $r['harga_satuan']);

        return [
            'obat_diprediksi' => $rows->count(),
            'total_obat_aktif' => $totalObatAktif,
            'obat_defisit' => $defisit->count(),
            'estimasi_anggaran' => $estimasi,
            'akurasi_avg' => $rows->map(fn ($r) => $r['akurasi'])->filter(fn ($v) => $v !== null)->avg() ?? $defaultAkurasi,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lonjakan(int $limit = 5): array
    {
        return array_slice($this->rows(), 0, $limit);
    }

    /**
     * Rincian per obat untuk modal Detail: ringkasan, prediksi per bulan
     * (dengan CI), tren realisasi 12 bulan, info model, stok gudang, faktor.
     *
     * @return array<string, mixed>|null
     */
    public function detail(int $obatId): ?array
    {
        $summary = collect($this->rows())->firstWhere('obat_id', $obatId);
        if (! $summary) {
            return null;
        }

        $periods = $this->periods();
        $anchor = Carbon::create($this->tahun, $this->bulan, 1);

        $monthlyQuery = PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun, periode_bulan, SUM(jumlah_prediksi) as jumlah, SUM(confidence_lower) as lower, SUM(confidence_upper) as upper, MAX(prediksi_kebutuhan.metode) as metode')
            ->where('obat_id', $obatId)
            ->where(function ($q) use ($periods) {
                foreach ($periods as $i => $period) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method(fn ($q2) => $q2
                        ->where('periode_tahun', $period['y'])
                        ->where('periode_bulan', $period['m'])
                    );
                }
            })
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderBy('periode_tahun')
            ->orderBy('periode_bulan');
        $monthlyQuery = $this->applyScope($monthlyQuery, 'prediksi_kebutuhan.fasilitas_id');
        $monthlyRaw = $monthlyQuery->get()->keyBy(fn ($r) => (int) $r->periode_tahun.'-'.(int) $r->periode_bulan);

        $bulanan = [];
        foreach ($periods as $period) {
            $row = $monthlyRaw->get($period['y'].'-'.$period['m']);
            $bulanan[] = [
                'label' => Carbon::create($period['y'], $period['m'])->translatedFormat('M Y'),
                'jumlah' => (int) ($row?->jumlah ?? 0),
                'lower' => $row?->lower !== null ? (int) $row->lower : null,
                'upper' => $row?->upper !== null ? (int) $row->upper : null,
                'metode' => $row?->metode,
            ];
        }

        $trenStart = $anchor->copy()->subMonths(12)->startOfMonth();
        $trenEnd = $anchor->copy()->subMonth()->endOfMonth();

        $trenSelect = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', p.tanggal_pemakaian) as y, strftime('%m', p.tanggal_pemakaian) as m, SUM(d.jumlah) as total"
            : 'YEAR(p.tanggal_pemakaian) as y, MONTH(p.tanggal_pemakaian) as m, SUM(d.jumlah) as total';

        $realQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->where('d.obat_id', $obatId)
            ->whereBetween('p.tanggal_pemakaian', [$trenStart->format('Y-m-d'), $trenEnd->format('Y-m-d')])
            ->selectRaw($trenSelect)
            ->groupBy('y', 'm');

        if ($this->fasilitasId) {
            $realQuery->where('p.fasilitas_id', $this->fasilitasId);
        } elseif (! empty($this->visibleFasilitasIds)) {
            $realQuery->whereIn('p.fasilitas_id', $this->visibleFasilitasIds);
        }

        $realRaw = $realQuery->get()->keyBy(fn ($r) => (int) $r->y.'-'.(int) $r->m);

        $tren = [];
        for ($i = 12; $i >= 1; $i--) {
            $d = $anchor->copy()->subMonths($i);
            $key = (int) $d->format('Y').'-'.(int) $d->format('n');
            $tren[] = [
                'label' => $d->format('M'),
                'jumlah' => (int) ($realRaw->get($key)?->total ?? 0),
            ];
        }

        $modelQuery = ModelPrediksi::query()
            ->where('obat_id', $obatId)
            ->orderByDesc('updated_at');
        $modelQuery = $this->applyScope($modelQuery, 'model_prediksi.fasilitas_id');
        $models = $modelQuery->get();
        $model = $models->firstWhere('status', 'aktif') ?? $models->first();

        $stokGudang = (int) StokGudang::where('obat_id', $obatId)->sum('jumlah');
        $safety = (int) ceil($summary['prediksi_horizon'] * self::SAFETY_STOCK_RATE);

        $factors = [];
        if ($summary['rekom'] > 0 && $summary['coverage_days'] < 21) {
            $factors[] = [
                'judul' => 'Stok di bawah ambang aman',
                'sub' => 'Coverage '.number_format($summary['coverage_days'], 1, ',', '.').' hari (< 21 hari)',
            ];
        }
        $last3 = array_sum(array_column(array_slice($tren, -3), 'jumlah'));
        $prev3 = array_sum(array_column(array_slice($tren, -6, 3), 'jumlah'));
        if ($prev3 > 0) {
            $pct = (($last3 - $prev3) / $prev3) * 100;
            if ($pct > 5) {
                $factors[] = ['judul' => 'Konsumsi naik '.number_format($pct, 1, ',', '.').'%', 'sub' => '3 bulan terakhir vs 3 bulan sebelumnya'];
            } elseif ($pct < -5) {
                $factors[] = ['judul' => 'Konsumsi turun '.number_format(abs($pct), 1, ',', '.').'%', 'sub' => '3 bulan terakhir vs 3 bulan sebelumnya'];
            } else {
                $factors[] = ['judul' => 'Konsumsi stabil', 'sub' => 'Perubahan < 5% dalam 6 bulan terakhir'];
            }
        }
        if ($summary['rekom'] > 0) {
            $factors[] = [
                'judul' => 'Defisit '.number_format($summary['rekom'], 0, ',', '.').' '.$summary['satuan'],
                'sub' => 'Kebutuhan '.$this->horizon.' bulan ke depan setelah safety stock',
            ];
        }
        if ($summary['akurasi'] !== null && $summary['akurasi'] > 0) {
            $factors[] = ['judul' => 'Model '.($summary['metode'] ?? 'AI'), 'sub' => 'Confidence '.number_format($summary['akurasi'] * 100, 1, ',', '.').'%'];
        } else {
            $factors[] = ['judul' => 'Akurasi model belum terukur', 'sub' => 'Pantau realisasi pemakaian sebagai pembanding'];
        }
        if ($summary['rekom'] > 0) {
            $factors[] = $stokGudang >= $summary['rekom']
                ? ['judul' => 'Stok gudang mencukupi', 'sub' => number_format($stokGudang, 0, ',', '.').' '.$summary['satuan'].' tersedia di Gudang Dinas']
                : ['judul' => 'Stok gudang belum mencukupi', 'sub' => number_format($stokGudang, 0, ',', '.').' '.$summary['satuan'].' — perlu pengadaan tambahan'];
        }

        return [
            'ringkasan' => $summary,
            'bulanan' => $bulanan,
            'tren' => $tren,
            'tren_max' => max(1, max(array_column($tren, 'jumlah'))),
            'model' => $model ? [
                'status' => $model->status,
                'akurasi_r2' => $model->akurasi_r2 !== null ? (float) $model->akurasi_r2 : null,
                'mae' => $model->mae !== null ? (float) $model->mae : null,
                'mape' => $model->mape !== null ? (float) $model->mape : null,
                'tanggal_training' => $model->tanggal_training?->format('d/m/Y'),
                'data_training_count' => $model->data_training_count,
            ] : null,
            'stok_gudang' => $stokGudang,
            'safety' => $safety,
            'factors' => $factors,
        ];
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    protected function applyScope($query, string $column = 'fasilitas_id')
    {
        if ($this->fasilitasId) {
            return $query->where($column, $this->fasilitasId);
        }

        if (! empty($this->visibleFasilitasIds)) {
            return $query->whereIn($column, $this->visibleFasilitasIds);
        }

        return $query;
    }
}
