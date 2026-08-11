<?php

namespace App\Filament\Resources\PenerimaanStoks\Widgets;

use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PenerimaanStokStatsOverview extends StatsOverviewWidget
{
    public function getColumns(): int|array
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Penerimaan', PenerimaanStokResource::getEloquentQuery()->count())
                ->description('Semua penerimaan')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('gray'),
            Stat::make('Bulan Ini', PenerimaanStokResource::getEloquentQuery()
                ->whereMonth('tanggal_penerimaan', now()->month)
                ->whereYear('tanggal_penerimaan', now()->year)
                ->count())
                ->description('Penerimaan bulan '.now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Dikonfirmasi', PenerimaanStokResource::getEloquentQuery()->where('status', 'dikonfirmasi')->count())
                ->description('Sudah dikonfirmasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Draft', PenerimaanStokResource::getEloquentQuery()->where('status', 'draft')->count())
                ->description('Menunggu konfirmasi')
                ->descriptionIcon('heroicon-m-document')
                ->color('warning'),
        ];
    }
}
