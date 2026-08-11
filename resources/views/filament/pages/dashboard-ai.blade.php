<x-filament-panels::page>
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
            Filter Dashboard
        </h3>
        {{ $this->form }}
    </div>

    <div class="space-y-6">
        {{-- Row 1: Stats Overview (full width) --}}
        @livewire(App\Filament\Resources\DashboardAi\Widgets\PredictionStatsOverview::class)

        {{-- Row 2: Two columns --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(App\Filament\Resources\DashboardAi\Widgets\AccuracyDistributionChart::class)
            @livewire(App\Filament\Resources\DashboardAi\Widgets\TopPredictedDrugsChart::class)
        </div>

        {{-- Row 3: Prediction vs Actual (full width) --}}
        @livewire(App\Filament\Resources\DashboardAi\Widgets\PredictionVsActualChart::class)

        {{-- Row 4: Two columns --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(App\Filament\Resources\DashboardAi\Widgets\ModelStatusChart::class)
            @livewire(App\Filament\Resources\DashboardAi\Widgets\DrugTrendPredictionChart::class)
        </div>

        {{-- Row 5: Critical Alerts (full width) --}}
        @livewire(App\Filament\Resources\DashboardAi\Widgets\CriticalPredictionAlerts::class)
    </div>
</x-filament-panels::page>
