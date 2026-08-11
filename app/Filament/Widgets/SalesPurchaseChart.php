<?php

namespace App\Filament\Widgets;

use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SalesPurchaseChart extends Widget
{
    protected string $view = 'filament.widgets.sales-purchase-chart';

    protected int|string|array $columnSpan = 'full';

    public string $filter = '1M';

    public array $labels = [];

    public array $penerimaan = [];

    public array $permintaan = [];

    public ?array $chartOptions = null;

    public function mount(): void
    {
        $this->loadChartData();
    }

    public function updatedFilter(): void
    {
        $this->loadChartData();
    }

    protected function loadChartData(): void
    {
        $now = Carbon::now();
        $user = Auth::user();
        $fasilitasId = $user?->fasilitas_kesehatan_id;

        [$startDate, $points, $bucketKeyFn, $labelFn] = match ($this->filter) {
            '1M' => [
                $now->copy()->subDays(29)->startOfDay(),
                collect(range(0, 29))->map(fn ($i) => $now->copy()->subDays(29 - $i)),
                fn (Carbon $d) => $d->format('Y-m-d'),
                fn (Carbon $d) => $d->format('d M'),
            ],
            '3M' => [
                $now->copy()->subWeeks(12)->startOfWeek()->startOfDay(),
                collect(range(0, 12))->map(fn ($i) => $now->copy()->subWeeks(12 - $i)->startOfWeek()),
                fn (Carbon $d) => $d->copy()->startOfWeek()->format('Y-m-d'),
                fn (Carbon $d) => $d->format('d M'),
            ],
            '1Y' => [
                $now->copy()->subMonths(11)->startOfMonth(),
                collect(range(0, 11))->map(fn ($i) => $now->copy()->subMonths(11 - $i)->startOfMonth()),
                fn (Carbon $d) => $d->format('Y-m'),
                fn (Carbon $d) => $d->format('M Y'),
            ],
            default => [
                $now->copy()->subDays(29)->startOfDay(),
                collect(range(0, 29))->map(fn ($i) => $now->copy()->subDays(29 - $i)),
                fn (Carbon $d) => $d->format('Y-m-d'),
                fn (Carbon $d) => $d->format('d M'),
            ],
        };

        $permintaans = PermintaanObat::query()
            ->where('created_at', '>=', $startDate)
            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_pengirim_id', $fasilitasId))
            ->get(['created_at']);

        $penerimaans = PenerimaanStok::query()
            ->where('tanggal_penerimaan', '>=', $startDate)
            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId))
            ->get(['tanggal_penerimaan']);

        $permintaanByPeriod = $permintaans
            ->groupBy(fn ($item) => $bucketKeyFn($item->created_at))
            ->map->count();

        $penerimaanByPeriod = $penerimaans
            ->groupBy(fn ($item) => $bucketKeyFn($item->tanggal_penerimaan))
            ->map->count();

        $this->labels = [];
        $permintaanData = [];
        $penerimaanData = [];

        foreach ($points as $date) {
            $key = $bucketKeyFn($date);
            $this->labels[] = $labelFn($date);
            $permintaanData[] = (int) ($permintaanByPeriod[$key] ?? 0);
            $penerimaanData[] = (int) ($penerimaanByPeriod[$key] ?? 0);
        }

        $this->penerimaan = $penerimaanData;
        $this->permintaan = $permintaanData;

        $this->chartOptions = [
            'type' => 'bar',
            'data' => [
                'labels' => $this->labels,
                'datasets' => [
                    [
                        'label' => 'Penerimaan',
                        'data' => $penerimaanData,
                        'backgroundColor' => '#d1d5db',
                        'borderRadius' => 2,
                    ],
                    [
                        'label' => 'Permintaan',
                        'data' => $permintaanData,
                        'backgroundColor' => '#067D9B',
                        'borderRadius' => 2,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'grid' => [
                            'display' => false,
                        ],
                        'ticks' => [
                            'color' => '#9ca3af',
                            'font' => [
                                'size' => 11,
                            ],
                            'maxRotation' => 0,
                        ],
                    ],
                    'y' => [
                        'stacked' => true,
                        'grid' => [
                            'color' => 'rgba(229,231,235,0.5)',
                        ],
                        'ticks' => [
                            'color' => '#9ca3af',
                            'font' => [
                                'size' => 11,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, string> */
    protected function getFilters(): array
    {
        return [
            '1M' => '1 Bulan',
            '3M' => '3 Bulan',
            '1Y' => '1 Tahun',
        ];
    }
}
