<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use App\Models\SumberDana;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TrendPenggunaanChart extends ChartWidget
{
    protected ?string $heading = 'Trend Penggunaan Dana Bulanan';

    protected ?string $description = 'Total biaya per bulan, dikelompokkan per sumber dana';

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

        $labels = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create()->month($m)->translatedFormat('M');
        }

        $sumberDanaQuery = SumberDana::query()->where('tahun', $tahun);
        if ($this->sumber_dana_id) {
            $sumberDanaQuery->where('id', $this->sumber_dana_id);
        }
        $sumberDanas = $sumberDanaQuery->orderBy('kode')->get();

        $bulanData = DB::table('sumber_dana_penggunaan')
            ->whereYear('tanggal', $tahun)
            ->where('tipe', $this->tipe ?? 'realisasi')
            ->when($this->sumber_dana_id, fn ($q) => $q->where('sumber_dana_id', $this->sumber_dana_id))
            ->groupBy('sumber_dana_id', DB::raw('MONTH(tanggal)'))
            ->select(
                'sumber_dana_id',
                DB::raw('MONTH(tanggal) as bulan'),
                DB::raw('SUM(total_biaya) as total'),
            )
            ->get()
            ->groupBy('sumber_dana_id');

        $palette = [
            '#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#6366f1',
            '#14b8a6', '#a855f7',
        ];

        $datasets = [];
        $i = 0;
        foreach ($sumberDanas as $sd) {
            $rows = $bulanData->get($sd->id, collect());
            $monthlyTotals = [];
            for ($m = 1; $m <= 12; $m++) {
                $val = $rows->firstWhere('bulan', $m);
                $monthlyTotals[] = (float) ($val->total ?? 0);
            }
            $color = $palette[$i % count($palette)];
            $datasets[] = [
                'label' => $sd->kode,
                'data' => $monthlyTotals,
                'borderColor' => $color,
                'backgroundColor' => $color.'33',
                'borderWidth' => 2,
                'tension' => 0.3,
                'fill' => false,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
            ];
            $i++;
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
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
                        'callback' => "function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value);
                        }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            let value = context.parsed.y || 0;
                            return label + ': Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }",
                    ],
                ],
            ],
        ];
    }
}
