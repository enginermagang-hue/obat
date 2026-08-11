<?php

namespace App\Providers;

use App\Filament\Resources\PenerimaanStoks\Pages\ListPenerimaanStoks;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            fn (): string => view('filament.hooks.sumber-dana-alert')->render(),
            scopes: ListPenerimaanStoks::class,
        );
    }
}
