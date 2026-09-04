<?php

namespace App\Filament\Widgets;

use App\Services\AnalisisObatService;
use Filament\Widgets\ChartWidget;

class AnalisisTrenChart extends ChartWidget
{
    protected ?string $heading = 'Tren Konsumsi & Prediksi';

    protected ?string $description = 'Realisasi 12 bulan terakhir + forecast bulan ke depan (garis putus-putus)';

    protected ?string $maxHeight = '300px';

    public ?int $fasilitas_id = null;

    /** @var int[]|null */
    public ?array $visible_fasilitas_ids = null;

    public ?int $tahun = null;

    protected function getListeners(): array
    {
        return [
            'analisisFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->fasilitas_id = $filters['fasilitas_id'] ?? null;
        $this->visible_fasilitas_ids = $filters['visible_fasilitas_ids'] ?? null;
        $this->tahun = $filters['tahun'] ?? now()->year;

        $this->cachedData = null;
    }

    protected function getData(): array
    {
        $tren = (new AnalisisObatService(
            fasilitasId: $this->fasilitas_id,
            tahun: (int) ($this->tahun ?? now()->year),
            visibleFasilitasIds: $this->visible_fasilitas_ids,
        ))->tren(3);

        $palette = [
            ['border' => '#22c55e', 'bg' => 'rgba(34, 197, 94, 0.15)'],
            ['border' => '#067D9B', 'bg' => 'rgba(6, 125, 155, 0.15)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.15)'],
        ];

        $datasets = [];
        foreach ($tren['series'] as $i => $s) {
            $color = $palette[$i % count($palette)];
            $datasets[] = [
                'label' => $s['nama'],
                'data' => $s['realisasi'],
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.3,
                'spanGaps' => false,
            ];
            $datasets[] = [
                'label' => $s['nama'].' (prediksi)',
                'data' => $s['prediksi'],
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'borderWidth' => 2,
                'borderDash' => [5, 5],
                'fill' => false,
                'tension' => 0.3,
                'spanGaps' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $tren['labels'],
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
