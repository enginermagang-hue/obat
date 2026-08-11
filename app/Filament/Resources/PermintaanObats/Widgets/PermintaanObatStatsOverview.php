<?php

namespace App\Filament\Resources\PermintaanObats\Widgets;

use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PermintaanObatStatsOverview extends StatsOverviewWidget
{
    public function getColumns(): int|array
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Permintaan', PermintaanObatResource::getEloquentQuery()->count())
                ->description('Semua permintaan')
                ->descriptionIcon('heroicon-m-document-arrow-up')
                ->color('gray')
                ->extraAttributes(['class' => 'stat-bg-gray']),
            Stat::make('Bulan Ini', PermintaanObatResource::getEloquentQuery()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count())
                ->description('Permintaan bulan '.now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->extraAttributes(['class' => 'stat-bg-info']),
            Stat::make('Diterima', PermintaanObatResource::getEloquentQuery()->where('status', 'diterima')->count())
                ->description('Sudah diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes(['class' => 'stat-bg-success']),
            Stat::make('Ditolak', PermintaanObatResource::getEloquentQuery()->where('status', 'ditolak')->count())
                ->description('Permintaan ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->extraAttributes(['class' => 'stat-bg-danger']),
        ];
    }
}
