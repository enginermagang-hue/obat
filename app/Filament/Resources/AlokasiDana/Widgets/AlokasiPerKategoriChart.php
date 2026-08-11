<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AlokasiPerKategoriChart extends ChartWidget
{
    protected ?string $heading = 'Alokasi per Kategori Obat';

    protected ?string $description = 'Nilai current stock (harga_beli × jumlah) per kategori';

    protected ?string $maxHeight = '350px';

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

        $query = DB::table('batch_stok as bs')
            ->join('obat as o', 'o.id', '=', 'bs.obat_id')
            ->join('sumber_dana as sd', 'sd.id', '=', 'bs.sumber_dana_id')
            ->whereNotNull('bs.harga_beli')
            ->where('bs.jumlah', '>', 0)
            ->where('sd.tahun', $tahun)
            ->groupBy('o.kategori')
            ->orderByDesc(DB::raw('SUM(bs.jumlah * bs.harga_beli)'))
            ->select('o.kategori as nama_kategori', DB::raw('SUM(bs.jumlah * bs.harga_beli) as total_nilai'));

        if ($this->sumber_dana_id) {
            $query->where('bs.sumber_dana_id', $this->sumber_dana_id);
        }

        $rows = $query->get();

        $labels = $rows->map(fn ($r): string => $r->nama_kategori)->toArray();
        $values = $rows->map(fn ($r): float => (float) $r->total_nilai)->toArray();

        $palette = [
            '#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#6366f1',
            '#14b8a6', '#a855f7',
        ];
        $colors = array_slice($palette, 0, count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Stok',
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
                        'padding' => 12,
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
            'cutout' => '55%',
        ];
    }
}
