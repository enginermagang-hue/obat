<?php

namespace App\Filament\Resources\DistribusiObats\Widgets;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DistribusiObatStatsOverview extends StatsOverviewWidget
{
    public function getColumns(): int|array
    {
        return 5;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Distribusi', DistribusiObatResource::getEloquentQuery()->count())
                ->description('Semua distribusi')
                ->descriptionIcon('heroicon-m-truck')
                ->color('gray'),
            Stat::make('Draft', DistribusiObatResource::getEloquentQuery()->where('status', 'draft')->count())
                ->description('Menunggu dikirim')
                ->descriptionIcon('heroicon-m-document')
                ->color('gray'),
            Stat::make('Dalam Pengiriman', DistribusiObatResource::getEloquentQuery()->where('status', 'dalam_pengiriman')->count())
                ->description('Sedang dalam perjalanan')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
            Stat::make('Diterima', DistribusiObatResource::getEloquentQuery()->where('status', 'diterima')->count())
                ->description('Sudah diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Bulan Ini', DistribusiObatResource::getEloquentQuery()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count())
                ->description('Distribusi bulan '.now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
