<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode obat, nama obat..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="ruang-obat-filter-row">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ruang-obat-filter-label-inline">Status</span>
            @php
                $statusTabs = [
                    'semua' => 'Semua',
                    'tersedia' => 'Tersedia',
                    'menipis' => 'Menipis',
                    'habis' => 'Habis',
                ];
            @endphp

            @foreach ($statusTabs as $key => $label)
                <button wire:click="filterByStatus('{{ $key === 'semua' ? null : $key }}')" type="button"
                    class="ruang-obat-badge {{ ($activeStatus === ($key === 'semua' ? null : $key)) || ($key === 'semua' && blank($activeStatus)) ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                    {{ $label }}
                    <span class="ml-1 opacity-70">{{ $statusCounts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        <div x-data="{ open: false }" class="relative">
            @php
                $filterCount = 0;
                if (filled($filterKategori)) {
                    $filterCount++;
                }
                if (filled($filterFaskesId)) {
                    $filterCount++;
                }
            @endphp

            <button @click="open = !open" type="button" class="ruang-obat-filter-btn">
                <x-heroicon-m-funnel class="h-4 w-4" />
                Filter
                @if ($filterCount > 0)
                    <span class="ruang-obat-filter-badge">{{ $filterCount }}</span>
                @endif
                <x-heroicon-m-chevron-down class="h-3.5 w-3.5" />
            </button>

            <div x-show="open" @click.away="open = false" x-cloak class="ruang-obat-filter-dropdown">
                <div class="space-y-3">
                    <div>
                        <label class="ruang-obat-filter-label">Kategori</label>
                        <select wire:model.live="filterKategori" class="ruang-obat-filter-select">
                            <option value="">Semua Kategori</option>
                            @foreach ($kategoriOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isDinasUser)
                        <div>
                            <label class="ruang-obat-filter-label">Fasilitas</label>
                            <select wire:model.live="filterFaskesId" class="ruang-obat-filter-select">
                                <option value="">Semua Fasilitas</option>
                                @foreach ($faskesOptions as $id => $nama)
                                    <option value="{{ $id }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($filterCount > 0)
                        <button wire:click="resetFilters" @click="open = false" type="button" class="ruang-obat-filter-reset">
                            Reset Filter
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
