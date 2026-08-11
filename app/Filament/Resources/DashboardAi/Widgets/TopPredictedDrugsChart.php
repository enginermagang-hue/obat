<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopPredictedDrugsChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 Kebutuhan Tertinggi';

    protected ?string $description = 'Obat dengan prediksi kebutuhan terbesar bulan ini';

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
        $query = PrediksiKebutuhan::query()
            ->select('obat_id', DB::raw('SUM(jumlah_prediksi) as total_prediksi'))
            ->where('periode_bulan', $this->bulan)
            ->where('periode_tahun', $this->tahun)
            ->groupBy('obat_id')
            ->orderByDesc('total_prediksi')
            ->limit(10);

        if ($this->fasilitas_id) {
            $query->where('fasilitas_id', $this->fasilitas_id);
        }

        $data = $query->get();
        $obatIds = $data->pluck('obat_id')->toArray();

        $obatNames = [];
        if (! empty($obatIds)) {
            $obatNames = Obat::whereIn('id', $obatIds)
                ->pluck('nama_obat', 'id')
                ->toArray();
        }

        $labels = [];
        $values = [];

        foreach ($data as $item) {
            $labels[] = $obatNames[$item->obat_id] ?? "Obat #{$item->obat_id}";
            $values[] = (int) $item->total_prediksi;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prediksi Kebutuhan',
                    'data' => $values,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
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
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
