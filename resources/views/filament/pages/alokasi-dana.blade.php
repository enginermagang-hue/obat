<x-filament-panels::page>
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
            Filter Alokasi Dana
        </h3>
        {{ $this->form }}
    </div>

    <div class="space-y-6">
        {{-- Row 1: Stats Overview (full width) --}}
        @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiStatsOverview::class)

        {{-- Row 2: Donut + Bar --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\DistribusiDanaChart::class)
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\RealisasiPerTahunChart::class)
        </div>

        {{-- Row 3: Trend line (full width) --}}
        @livewire(App\Filament\Resources\AlokasiDana\Widgets\TrendPenggunaanChart::class)

        {{-- Row 4: Faskes + Kategori --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiPerFaskesChart::class)
            @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiPerKategoriChart::class)
        </div>

        {{-- Row 5: Top 10 Obat (full width) --}}
        @livewire(App\Filament\Resources\AlokasiDana\Widgets\TopObatDanaChart::class)

        {{-- Row 6: Summary Table (full width) --}}
        @livewire(App\Filament\Resources\AlokasiDana\Widgets\AlokasiSummaryTable::class)
    </div>
</x-filament-panels::page>
