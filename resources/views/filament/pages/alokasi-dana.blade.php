<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-3 flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-m-funnel"
                    class="h-4 w-4 text-primary-600 dark:text-primary-400"
                />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Filter
                </h3>
            </div>
            {{ $this->form }}
        </div>

        @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiStatsOverview::class)

        <div class="grid gap-4 xl:grid-cols-3">
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\DistribusiDanaChart::class)
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\RealisasiPerTahunChart::class)
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiPerKategoriChart::class)
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="xl:col-span-2">
                @livewire(App\Filament\Resources\AlokasiDana\Widgets\TrendPenggunaanChart::class)
            </div>
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiPerFaskesChart::class)
        </div>

        @livewire(App\Filament\Resources\AlokasiDana\Widgets\TopObatDanaChart::class)

        @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiSummaryTable::class)
    </div>
</x-filament-panels::page>
