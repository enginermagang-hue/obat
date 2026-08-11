<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\PrediksiKebutuhan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PredictionVsActualChart extends ChartWidget
{
    protected ?string $heading = 'Perbandingan Prediksi vs Realisasi';

    protected ?string $description = 'Total prediksi vs pemakaian aktual (6 bulan terakhir)';

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
        $labels = [];
        $prediksiData = [];
        $realisasiData = [];

        // Periode 6 bulan terakhir
        $periods = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::create($this->tahun, $this->bulan, 1)->subMonths($i);
            $m = (int) $date->format('n');
            $y = (int) $date->format('Y');
            $periods[] = ['tahun' => $y, 'bulan' => $m];
            $labels[] = Carbon::create()->month($m)->translatedFormat('M').' '.$date->format('Y');
        }

        // === Prediksi: single query ===
        $prediksiQuery = PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun, periode_bulan, SUM(jumlah_prediksi) as total');

        $prediksiQuery->where(function ($q) use ($periods) {
            foreach ($periods as $i => $period) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->$method(fn ($q2) => $q2
                    ->where('periode_tahun', $period['tahun'])
                    ->where('periode_bulan', $period['bulan'])
                );
            }
        });

        if ($this->fasilitas_id) {
            $prediksiQuery->where('fasilitas_id', $this->fasilitas_id);
        }

        if ($this->obat_id) {
            $prediksiQuery->where('obat_id', $this->obat_id);
        }

        $prediksiRaw = $prediksiQuery
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderBy('periode_tahun')
            ->orderBy('periode_bulan')
            ->get()
            ->keyBy(fn ($item) => $item->periode_tahun.'-'.str_pad((string) $item->periode_bulan, 2, '0', STR_PAD_LEFT));

        // === Realisasi: single query ===
        $startDate = Carbon::create($this->tahun, $this->bulan, 1)->subMonths(5)->startOfMonth();
        $endDate = Carbon::create($this->tahun, $this->bulan, 1)->endOfMonth();

        $realisasiQuery = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->whereBetween('p.tanggal_pemakaian', [$startDate, $endDate])
            ->selectRaw('YEAR(p.tanggal_pemakaian) as tahun, MONTH(p.tanggal_pemakaian) as bulan, SUM(d.jumlah) as total')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan');

        if ($this->fasilitas_id) {
            $realisasiQuery->where('p.fasilitas_id', $this->fasilitas_id);
        }

        if ($this->obat_id) {
            $realisasiQuery->where('d.obat_id', $this->obat_id);
        }

        $realisasiRaw = $realisasiQuery->get()->keyBy(
            fn ($item) => $item->tahun.'-'.str_pad((string) $item->bulan, 2, '0', STR_PAD_LEFT)
        );

        // === Map ke array berdasarkan periode ===
        foreach ($periods as $period) {
            $key = $period['tahun'].'-'.str_pad((string) $period['bulan'], 2, '0', STR_PAD_LEFT);
            $prediksiData[] = (int) (($prediksiRaw[$key] ?? null)?->total ?? 0);
            $realisasiData[] = (int) (($realisasiRaw[$key] ?? null)?->total ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prediksi',
                    'data' => $prediksiData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => '#36A2EB',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Realisasi',
                    'data' => $realisasiData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
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
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
