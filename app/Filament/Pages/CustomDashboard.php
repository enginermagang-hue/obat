<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AktivitasTerakhirWidget;
use App\Filament\Widgets\InventoryStatsWidget;
use App\Filament\Widgets\InventoryValueWidget;
use App\Filament\Widgets\OverallInformationWidget;
use App\Filament\Widgets\PemakaianTerbaruWidget;
use App\Filament\Widgets\RingkasanWidget;
use App\Filament\Widgets\SalesPurchaseChart;
use App\Filament\Widgets\SelamatDatangWidget;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;

class CustomDashboard extends Dashboard
{
    protected string $view = 'filament.pages.custom-dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        $user = Auth::user();

        return match (true) {
            $user?->hasRole('puskesmas') => [
                SelamatDatangWidget::class,
                InventoryStatsWidget::class,
                RingkasanWidget::class,
                PemakaianTerbaruWidget::class,
                AktivitasTerakhirWidget::class,
            ],
            $user?->hasRole('pustu') => [
                SelamatDatangWidget::class,
                InventoryStatsWidget::class,
                RingkasanWidget::class,
                PemakaianTerbaruWidget::class,
                AktivitasTerakhirWidget::class,
            ],
            default => [
                SelamatDatangWidget::class,
                InventoryStatsWidget::class,
                InventoryValueWidget::class,
                SalesPurchaseChart::class,
                OverallInformationWidget::class,
                RingkasanWidget::class,
                AktivitasTerakhirWidget::class,
            ],
        };
    }
}
