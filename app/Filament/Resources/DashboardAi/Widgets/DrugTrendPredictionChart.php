<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\PrediksiKebutuhan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DrugTrendPredictionChart extends ChartWidget
{
    protected ?string $heading = 'Tren Pemakaian + Prediksi';

    protected ?string $description = 'Data historis 12 bulan + prediksi 3 bulan ke depan';

    protected ?string $maxHeight = '300px';

    public ?int $fasilitas_id = null;

    public ?int $obat_id = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    protected function getListeners(): array
    {
        return [
            'dashboardFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->fasilitas_id = $filters['fasilitas_id'] ?? null;
        $this->obat_id = $filters['obat_id'] ?? null;
        $this->bulan = $filters['bulan'] ?? now()->month;
        $this->tahun = $filters['tahun'] ?? now()->year;

        $this->cachedData = null;
    }

    protected function getData(): array
    {
        $historyLabels = [];
        $historyData = [];
        $predictionLabels = [];
        $predictionData = [];

        // Current month
        $currentDate = Carbon::create($this->tahun, $this->bulan, 1);

        // === Historical: 12 months before current (single query) ===
        $startDate = (clone $currentDate)->subMonths(12)->startOfMonth();
        $endDate = (clone $currentDate)->subMonth()->endOfMonth();

        $historyQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereBetween('p.tanggal_pemakaian', [$startDate, $endDate])
            ->selectRaw('YEAR(p.tanggal_pemakaian) as tahun, MONTH(p.tanggal_pemakaian) as bulan, SUM(d.jumlah) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan');

        if ($this->fasilitas_id) {
            $historyQuery->where('p.fasilitas_id', $this->fasilitas_id);
        }

        if ($this->obat_id) {
            $historyQuery->where('d.obat_id', $this->obat_id);
        }

        $historyRaw = $historyQuery->get()->keyBy(
            fn ($item) => $item->tahun.'-'.str_pad((string) $item->bulan, 2, '0', STR_PAD_LEFT)
        );

        for ($i = 12; $i >= 1; $i--) {
            $date = (clone $currentDate)->subMonths($i);
            $key = $date->format('Y-m');
            $historyLabels[] = Carbon::create()->month((int) $date->format('n'))->translatedFormat('M').' '.$date->format('Y');
            $historyData[] = (int) (($historyRaw[$key] ?? null)?->total ?? 0);
        }

        // === Predictions: current month + 2 more months (single query) ===
        $predictionPeriods = [];
        for ($i = 0; $i <= 2; $i++) {
            $date = (clone $currentDate)->addMonths($i);
            $m = (int) $date->format('n');
            $y = (int) $date->format('Y');
            $predictionPeriods[] = ['tahun' => $y, 'bulan' => $m];
            $predictionLabels[] = Carbon::create()->month($m)->translatedFormat('M').' '.$date->format('Y');
        }

        $prediksiQuery = PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun, periode_bulan, SUM(jumlah_prediksi) as total');

        $prediksiQuery->where(function ($q) use ($predictionPeriods) {
            foreach ($predictionPeriods as $i => $period) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->$method(fn ($q2) => $q2
                    ->where('periode_tahun', $period['tahun'])
                    ->where('periode_bulan', $period['bulan'])
                );
            }
        });

        if ($this->fasilitas_id) {
            $prediksiQuery->where('fasilitas_id', $this->fasilitas_id);
        }

        if ($this->obat_id) {
            $prediksiQuery->where('obat_id', $this->obat_id);
        }

        $prediksiRaw = $prediksiQuery
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderBy('periode_tahun')
            ->orderBy('periode_bulan')
            ->get()
            ->keyBy(fn ($item) => $item->periode_tahun.'-'.str_pad((string) $item->periode_bulan, 2, '0', STR_PAD_LEFT));

        foreach ($predictionPeriods as $period) {
            $key = $period['tahun'].'-'.str_pad((string) $period['bulan'], 2, '0', STR_PAD_LEFT);
            $predictionData[] = (int) (($prediksiRaw[$key] ?? null)?->total ?? 0);
        }

        // Combine labels: history + prediction
        $allLabels = array_merge($historyLabels, $predictionLabels);

        // For predictions, pad with null for historical period
        $filledPrediction = array_merge(
            array_fill(0, count($historyLabels), null),
            $predictionData
        );

        return [
            'datasets' => [
                [
                    'label' => 'Pemakaian Aktual',
                    'data' => array_merge($historyData, array_fill(0, count($predictionData), null)),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Prediksi AI',
                    'data' => $filledPrediction,
                    'backgroundColor' => 'rgba(251, 146, 60, 0.2)',
                    'borderColor' => '#fb923c',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'fill' => true,
                    'tension' => 0.3,
                    'spanGaps' => false,
                ],
            ],
            'labels' => $allLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
