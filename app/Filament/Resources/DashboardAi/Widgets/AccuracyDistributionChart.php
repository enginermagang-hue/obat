<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\ModelPrediksi;
use Filament\Widgets\ChartWidget;

class AccuracyDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Akurasi Model';

    protected ?string $description = 'Sebaran R² score model prediksi';

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
        $query = ModelPrediksi::query()->where('status', 'aktif');

        if ($this->fasilitas_id) {
            $query->where('fasilitas_id', $this->fasilitas_id);
        }

        $models = (clone $query)->whereNotNull('akurasi_r2')->pluck('akurasi_r2');

        $ranges = [
            '0 - 25%' => 0,
            '25% - 50%' => 0,
            '50% - 75%' => 0,
            '75% - 100%' => 0,
        ];

        foreach ($models as $r2) {
            $r2 = (float) $r2;
            if ($r2 < 0.25) {
                $ranges['0 - 25%']++;
            } elseif ($r2 < 0.5) {
                $ranges['25% - 50%']++;
            } elseif ($r2 < 0.75) {
                $ranges['50% - 75%']++;
            } else {
                $ranges['75% - 100%']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Model',
                    'data' => array_values($ranges),
                    'backgroundColor' => [
                        '#ef4444',
                        '#f97316',
                        '#eab308',
                        '#22c55e',
                    ],
                    'borderColor' => [
                        '#dc2626',
                        '#ea580c',
                        '#ca8a04',
                        '#16a34a',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($ranges),
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
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
