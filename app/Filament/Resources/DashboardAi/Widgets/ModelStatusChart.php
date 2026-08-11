<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\ModelPrediksi;
use Filament\Widgets\ChartWidget;

class ModelStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status Model';

    protected ?string $description = 'Distribusi status model prediksi';

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
        $query = ModelPrediksi::query();

        if ($this->fasilitas_id) {
            $query->where('fasilitas_id', $this->fasilitas_id);
        }

        $statuses = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusLabels = [
            'aktif' => 'Aktif',
            'kadaluarsa' => 'Kadaluarsa',
            'gagal' => 'Gagal',
            'data_belum_cukup' => 'Data Belum Cukup',
        ];

        $statusColors = [
            'aktif' => '#22c55e',
            'kadaluarsa' => '#f97316',
            'gagal' => '#ef4444',
            'data_belum_cukup' => '#6b7280',
        ];

        $labels = [];
        $data = [];
        $colors = [];
        $borders = [];

        foreach ($statuses as $status => $total) {
            $labels[] = $statusLabels[$status] ?? ucfirst($status);
            $data[] = $total;
            $colors[] = $statusColors[$status] ?? '#6b7280';
            $borders[] = '#ffffff';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Model',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
