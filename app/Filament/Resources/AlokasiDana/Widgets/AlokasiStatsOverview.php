<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use App\Models\SumberDana;
use App\Models\SumberDanaPenggunaan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlokasiStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Alokasi Dana';

    protected ?string $description = 'Anggaran, realisasi, dan sisa dana tahun terpilih';

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

        $this->cachedStats = null;
    }

    protected function getStats(): array
    {
        $tahun = $this->tahun ?? now()->year;

        $anggaranQuery = SumberDana::query()->where('tahun', $tahun);
        if ($this->sumber_dana_id) {
            $anggaranQuery->where('id', $this->sumber_dana_id);
        }
        $totalAnggaran = (float) (clone $anggaranQuery)->sum('total_anggaran');

        $realisasiQuery = SumberDanaPenggunaan::query()
            ->where('tipe', 'realisasi')
            ->whereYear('tanggal', $tahun);
        if ($this->sumber_dana_id) {
            $realisasiQuery->where('sumber_dana_id', $this->sumber_dana_id);
        }
        $totalRealisasi = (float) (clone $realisasiQuery)->sum('total_biaya');

        $perencanaanQuery = SumberDanaPenggunaan::query()
            ->where('tipe', 'perencanaan')
            ->whereYear('tanggal', $tahun);
        if ($this->sumber_dana_id) {
            $perencanaanQuery->where('sumber_dana_id', $this->sumber_dana_id);
        }
        $totalPerencanaan = (float) (clone $perencanaanQuery)->sum('total_biaya');

        $alokasiQuery = SumberDanaPenggunaan::query()
            ->where('tipe', 'alokasi')
            ->whereYear('tanggal', $tahun);
        if ($this->sumber_dana_id) {
            $alokasiQuery->where('sumber_dana_id', $this->sumber_dana_id);
        }
        $totalAlokasi = (float) (clone $alokasiQuery)->sum('total_biaya');

        $sisa = $totalAnggaran - $totalRealisasi;
        $persen = $totalAnggaran > 0 ? ($totalRealisasi / $totalAnggaran) * 100 : 0;

        return [
            Stat::make('Total Anggaran', 'Rp '.number_format($totalAnggaran, 0, ',', '.'))
                ->description("Tahun anggaran {$tahun}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Total Realisasi', 'Rp '.number_format($totalRealisasi, 0, ',', '.'))
                ->description($totalRealisasi > 0 ? 'Pembelian dari supplier' : 'Belum ada realisasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Alokasi', 'Rp '.number_format($totalAlokasi, 0, ',', '.'))
                ->description($totalAlokasi > 0 ? 'Didistribusikan ke faskes' : 'Belum ada alokasi')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Total Perencanaan', 'Rp '.number_format($totalPerencanaan, 0, ',', '.'))
                ->description($totalPerencanaan === 0.0 ? 'Belum ada RKO' : 'Rencana dari RKO')
                ->descriptionIcon('heroicon-m-document-chart-bar')
                ->color($totalPerencanaan === 0.0 ? 'gray' : 'warning'),

            Stat::make('Sisa Anggaran', 'Rp '.number_format($sisa, 0, ',', '.'))
                ->description($sisa < 0 ? 'Over anggaran!' : 'Masih tersedia')
                ->descriptionIcon($sisa < 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-wallet')
                ->color($sisa < 0 ? 'danger' : 'info'),

            Stat::make('% Terpakai', number_format($persen, 1).'%')
                ->description('Realisasi / Anggaran')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color(match (true) {
                    $persen > 80 => 'danger',
                    $persen > 50 => 'warning',
                    default => 'success',
                }),
        ];
    }
}
