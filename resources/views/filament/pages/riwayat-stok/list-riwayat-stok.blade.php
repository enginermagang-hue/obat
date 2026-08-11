<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode obat, nama obat, keterangan..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="ruang-obat-filter-row">
        @if ($isDinasUser)
            <div class="flex items-center gap-2">
                <span class="ruang-obat-filter-label-inline">Fasilitas</span>
                <select wire:model.live="filterFaskesId" class="w-56 ruang-obat-inline-select">
                    @foreach ($faskesOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div></div>
        @endif

        <div x-data="{ open: false }" class="relative">
            @php
                $filterCount = count($filterTipe ?? []);
                if (filled($filterTanggalFrom)) {
                    $filterCount++;
                }
                if (filled($filterTanggalTo)) {
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
                <div class="space-y-4">
                    <div x-data="{
                        filterTipe: $wire.entangle('filterTipe'),
                    }" x-init="$watch('filterTipe', () => $wire.call('applyFilterTipe'))">
                        <label class="ruang-obat-filter-label">Tipe Transaksi</label>
                        <div class="space-y-1.5">
                            @foreach ($tipeOptions as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="filterTipe" value="{{ $key }}"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">{{ $tipeCounts[$key] ?? 0 }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3 dark:border-gray-700">
                        <label class="ruang-obat-filter-label">Tanggal Dari</label>
                        <input type="date" wire:model.live="filterTanggalFrom" class="ruang-obat-filter-select">
                    </div>

                    <div>
                        <label class="ruang-obat-filter-label">Tanggal Sampai</label>
                        <input type="date" wire:model.live="filterTanggalTo" class="ruang-obat-filter-select">
                    </div>

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
