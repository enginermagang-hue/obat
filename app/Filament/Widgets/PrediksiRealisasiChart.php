<?php

namespace App\Filament\Widgets;

use App\Models\PrediksiKebutuhan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PrediksiRealisasiChart extends ChartWidget
{
    protected ?string $heading = 'Prediksi vs Realisasi — 6 Bulan Terakhir';

    protected ?string $description = 'Bar = realisasi pemakaian, garis = prediksi AI. Catatan: prediksi hanya tersedia untuk bulan ke depan.';

    protected ?string $maxHeight = '300px';

    public ?int $fasilitas_id = null;

    /** @var int[]|null */
    public ?array $visible_fasilitas_ids = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    public int $horizon = 3;

    protected function getListeners(): array
    {
        return [
            'prediksiFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->fasilitas_id = $filters['fasilitas_id'] ?? null;
        $this->visible_fasilitas_ids = $filters['visible_fasilitas_ids'] ?? null;
        $this->bulan = $filters['bulan'] ?? now()->month;
        $this->tahun = $filters['tahun'] ?? now()->year;
        $this->horizon = max(1, (int) ($filters['horizon'] ?? 3));

        $this->cachedData = null;
    }

    protected function getData(): array
    {
        $anchor = Carbon::create($this->tahun ?? now()->year, $this->bulan ?? now()->month, 1);
        $horizon = max(1, $this->horizon);

        $past = [];
        for ($i = 6; $i >= 1; $i--) {
            $past[] = $anchor->copy()->subMonths($i);
        }
        $future = [];
        for ($i = 0; $i < $horizon; $i++) {
            $future[] = $anchor->copy()->addMonths($i);
        }

        $labels = [];
        $historyMap = [];
        $predictionMap = [];

        foreach (array_merge($past, $future) as $d) {
            $key = (int) $d->format('Y').'-'.(int) $d->format('n');
            $labels[] = $d->format('M').' '.$d->format('Y');
            $historyMap[$key] = null;
            $predictionMap[$key] = null;
        }

        $fasilitasId = $this->fasilitas_id;
        $visibleIds = $this->visible_fasilitas_ids;

        $realisasiQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereBetween('p.tanggal_pemakaian', [$past[0]->format('Y-m-d'), end($past)->format('Y-m-t')])
            ->selectRaw('YEAR(p.tanggal_pemakaian) as y, MONTH(p.tanggal_pemakaian) as m, SUM(d.jumlah) as total')
            ->groupBy('y', 'm');

        if ($fasilitasId) {
            $realisasiQuery->where('p.fasilitas_id', $fasilitasId);
        } elseif (! empty($visibleIds)) {
            $realisasiQuery->whereIn('p.fasilitas_id', $visibleIds);
        }

        foreach ($realisasiQuery->get() as $r) {
            $historyMap[(int) $r->y.'-'.(int) $r->m] = (int) $r->total;
        }

        $prediksiQuery = PrediksiKebutuhan::selectRaw('periode_tahun as y, periode_bulan as m, SUM(jumlah_prediksi) as total')
            ->where(function ($q) use ($future) {
                foreach ($future as $i => $d) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->$method(fn ($q2) => $q2
                        ->where('periode_tahun', (int) $d->format('Y'))
                        ->where('periode_bulan', (int) $d->format('n'))
                    );
                }
            })
            ->groupBy('y', 'm');

        if ($fasilitasId) {
            $prediksiQuery->where('fasilitas_id', $fasilitasId);
        } elseif (! empty($visibleIds)) {
            $prediksiQuery->whereIn('fasilitas_id', $visibleIds);
        }

        foreach ($prediksiQuery->get() as $r) {
            $predictionMap[(int) $r->y.'-'.(int) $r->m] = (int) $r->total;
        }

        $pastCount = count($past);
        $historyData = array_slice(array_values($historyMap), 0, $pastCount);
        $predictionData = array_slice(array_values($predictionMap), $pastCount);

        return [
            'datasets' => [
                [
                    'label' => 'Realisasi',
                    'data' => array_merge($historyData, array_fill(0, count($predictionData), null)),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Prediksi AI',
                    'data' => array_merge(array_fill(0, count($historyData), null), $predictionData),
                    'backgroundColor' => 'rgba(6, 125, 155, 0.6)',
                    'borderColor' => '#067D9B',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                    'spanGaps' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
