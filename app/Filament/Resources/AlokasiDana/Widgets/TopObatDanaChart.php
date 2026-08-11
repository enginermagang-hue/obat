<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopObatDanaChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 Obat dengan Alokasi Dana Terbesar';

    protected ?string $description = 'Nilai current stock (harga_beli × jumlah) per obat';

    protected ?string $maxHeight = '380px';

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
            ->groupBy('o.id', 'o.nama_obat')
            ->orderByDesc(DB::raw('SUM(bs.jumlah * bs.harga_beli)'))
            ->limit(10)
            ->select('o.nama_obat', DB::raw('SUM(bs.jumlah * bs.harga_beli) as total_nilai'));

        if ($this->sumber_dana_id) {
            $query->where('bs.sumber_dana_id', $this->sumber_dana_id);
        }

        $rows = $query->get();

        $labels = $rows->map(fn ($r): string => mb_strimwidth($r->nama_obat, 0, 40, '…'))->toArray();
        $values = $rows->map(fn ($r): float => (float) $r->total_nilai)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Stok',
                    'data' => $values,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                    'borderColor' => '#f59e0b',
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
                        'callback' => "function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value);
                        }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            let value = context.parsed.x || 0;
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }",
                    ],
                ],
            ],
        ];
    }
}
