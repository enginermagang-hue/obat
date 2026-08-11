<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, telepon, email..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="flex items-center gap-3 pb-3">
        <span class="ruang-obat-filter-label-inline">Status</span>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="filterByStatus(null)" type="button"
                class="ruang-obat-badge {{ is_null($filterStatus) ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Semua
            </button>
            <button wire:click="filterByStatus('aktif')" type="button"
                class="ruang-obat-badge {{ $filterStatus === 'aktif' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Aktif
                <span class="ml-1 opacity-70">{{ $statusCounts['aktif'] ?? 0 }}</span>
            </button>
            <button wire:click="filterByStatus('nonaktif')" type="button"
                class="ruang-obat-badge {{ $filterStatus === 'nonaktif' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Nonaktif
                <span class="ml-1 opacity-70">{{ $statusCounts['nonaktif'] ?? 0 }}</span>
            </button>
        </div>
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
