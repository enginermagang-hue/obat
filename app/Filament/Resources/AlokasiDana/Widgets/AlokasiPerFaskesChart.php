<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AlokasiPerFaskesChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 Fasilitas Penerima Alokasi';

    protected ?string $description = 'Hanya menampilkan alokasi tingkat faskes (data dinas tidak termasuk)';

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

        $query = DB::table('sumber_dana_penggunaan as sp')
            ->join('fasilitas_kesehatan as fk', 'fk.id', '=', 'sp.fasilitas_id')
            ->whereNotNull('sp.fasilitas_id')
            ->whereYear('sp.tanggal', $tahun)
            ->where('sp.tipe', $this->tipe ?? 'alokasi')
            ->groupBy('fk.id', 'fk.nama')
            ->orderByDesc(DB::raw('SUM(sp.total_biaya)'))
            ->limit(10)
            ->select('fk.nama', DB::raw('SUM(sp.total_biaya) as total_biaya'));

        if ($this->sumber_dana_id) {
            $query->where('sp.sumber_dana_id', $this->sumber_dana_id);
        }

        $rows = $query->get();

        $labels = $rows->map(fn ($r): string => $r->nama)->toArray();
        $values = $rows->map(fn ($r): float => (float) $r->total_biaya)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Alokasi',
                    'data' => $values,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => '#3b82f6',
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
