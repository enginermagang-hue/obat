<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari deskripsi, log, event, user..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 pb-3">
        <span class="ruang-obat-filter-label-inline">Log</span>
        <select wire:model.live="filterLogName" class="ruang-obat-inline-select">
            <option value="">Semua Log</option>
            @foreach ($logNameOptions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <span class="ruang-obat-filter-label-inline">Event</span>
        <select wire:model.live="filterEvent" class="ruang-obat-inline-select">
            <option value="">Semua Event</option>
            @foreach ($eventOptions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <span class="ruang-obat-filter-label-inline">Dari</span>
        <input type="date" wire:model.live="filterTanggalFrom"
            class="ruang-obat-inline-select">

        <span class="ruang-obat-filter-label-inline">Sampai</span>
        <input type="date" wire:model.live="filterTanggalTo"
            class="ruang-obat-inline-select">

        @if (filled($filterLogName) || filled($filterEvent) || filled($filterTanggalFrom) || filled($filterTanggalTo))
            <button wire:click="resetFilters" type="button"
                class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                Reset
            </button>
        @endif
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
