<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use App\Models\SumberDana;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RealisasiPerTahunChart extends ChartWidget
{
    protected ?string $heading = 'Anggaran vs Realisasi per Tahun';

    protected ?string $description = 'Perbandingan total anggaran dan total realisasi pertahun';

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

        $tahuns = range($tahun - 2, $tahun + 1);

        $anggaranPerTahun = SumberDana::query()
            ->whereIn('tahun', $tahuns)
            ->when($this->sumber_dana_id, fn ($q) => $q->where('id', $this->sumber_dana_id))
            ->groupBy('tahun')
            ->select('tahun', DB::raw('SUM(total_anggaran) as total'))
            ->pluck('total', 'tahun')
            ->toArray();

        $realisasiPerTahun = DB::table('sumber_dana_penggunaan')
            ->whereIn(DB::raw('YEAR(tanggal)'), $tahuns)
            ->where('tipe', 'realisasi')
            ->when($this->sumber_dana_id, fn ($q) => $q->where('sumber_dana_id', $this->sumber_dana_id))
            ->groupBy(DB::raw('YEAR(tanggal)'))
            ->select(DB::raw('YEAR(tanggal) as tahun'), DB::raw('SUM(total_biaya) as total'))
            ->pluck('total', 'tahun')
            ->toArray();

        $anggaranValues = [];
        $realisasiValues = [];
        foreach ($tahuns as $th) {
            $anggaranValues[] = (float) ($anggaranPerTahun[$th] ?? 0);
            $realisasiValues[] = (float) ($realisasiPerTahun[$th] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Anggaran',
                    'data' => $anggaranValues,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Realisasi',
                    'data' => $realisasiValues,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_map('strval', $tahuns),
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
