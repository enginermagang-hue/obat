<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\ModelPrediksi;
use App\Models\PrediksiKebutuhan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PredictionStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Prediksi';

    protected ?string $description = 'Statistik utama model dan hasil prediksi';

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

        $this->cachedStats = null;
    }

    protected function getStats(): array
    {
        $modelQuery = ModelPrediksi::query();
        $prediksiQuery = PrediksiKebutuhan::query();

        if ($this->fasilitas_id) {
            $modelQuery->where('fasilitas_id', $this->fasilitas_id);
            $prediksiQuery->where('fasilitas_id', $this->fasilitas_id);
        }

        $totalModelAktif = (clone $modelQuery)->where('status', 'aktif')->count();
        $totalObatDiprediksi = (clone $prediksiQuery)
            ->where('periode_bulan', $this->bulan)
            ->where('periode_tahun', $this->tahun)
            ->distinct('obat_id')
            ->count('obat_id');

        $rataAkurasi = (clone $modelQuery)
            ->where('status', 'aktif')
            ->whereNotNull('akurasi_r2')
            ->avg('akurasi_r2');

        $totalFaskes = (clone $modelQuery)
            ->distinct('fasilitas_id')
            ->count('fasilitas_id');

        return [
            Stat::make('Model Aktif', number_format($totalModelAktif))
                ->description('Model dengan status aktif')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('success'),

            Stat::make('Obat Diprediksi', number_format($totalObatDiprediksi))
                ->description('Bulan '.Carbon::create()->month($this->bulan)->translatedFormat('F')." {$this->tahun}")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Rata-rata Akurasi', $rataAkurasi !== null ? number_format($rataAkurasi * 100, 1).'%' : 'N/A')
                ->description('R² score rata-rata')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color($rataAkurasi !== null && $rataAkurasi >= 0.7 ? 'success' : 'warning'),

            Stat::make('Faskes Terprediksi', number_format($totalFaskes))
                ->description('Fasilitas dengan model AI')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
        ];
    }
}
