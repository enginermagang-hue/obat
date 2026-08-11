<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DistribusiDanaChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Realisasi per Sumber Dana';

    protected ?string $description = 'Pembagian total_biaya per sumber dana';

    protected ?string $maxHeight = '320px';

    public ?int $tahun = null;

    public ?int $sumber_dana_id = null;

    public ?string $tipe = null;

    protected function getListeners(): array
    {
        return [
            'alokasiDanaFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->tahun = $filters['tahun'] ?? now()->year;
        $this->sumber_dana_id = $filters['sumber_dana_id'] ?? null;
        $this->tipe = $filters['tipe'] ?? null;

        $this->cachedData = null;
    }

    protected function getData(): array
    {
        $tahun = $this->tahun ?? now()->year;

        $query = DB::table('sumber_dana_penggunaan as sp')
            ->join('sumber_dana as sd', 'sd.id', '=', 'sp.sumber_dana_id')
            ->whereYear('sp.tanggal', $tahun)
            ->where('sp.tipe', $this->tipe ?? 'alokasi')
            ->groupBy('sd.kode', 'sd.nama')
            ->orderByDesc(DB::raw('SUM(sp.total_biaya)'))
            ->select(
                'sd.kode',
                'sd.nama',
                DB::raw('SUM(sp.total_biaya) as total_biaya'),
            );

        if ($this->sumber_dana_id) {
            $query->where('sp.sumber_dana_id', $this->sumber_dana_id);
        }

        $rows = $query->get();

        $labels = $rows->map(fn ($r): string => $r->kode)->toArray();
        $values = $rows->map(fn ($r): float => (float) $r->total_biaya)->toArray();

        $palette = [
            '#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#6366f1',
            '#14b8a6', '#a855f7',
        ];
        $colors = array_slice($palette, 0, count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Total Biaya',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
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
                        'padding' => 15,
                        'usePointStyle' => true,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            return label + ': Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }",
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
