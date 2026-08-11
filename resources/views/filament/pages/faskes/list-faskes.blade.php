<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="ruang-obat-search-input-icon">
                <x-heroicon-m-magnifying-glass class="" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, PIC, alamat..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="ruang-obat-filter-row">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ruang-obat-filter-label-inline">Tipe</span>
            <button wire:click="filterByTipe(null)" type="button"
                class="ruang-obat-badge {{ is_null($filterTipe) ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Semua
            </button>
            <button wire:click="filterByTipe('puskesmas')" type="button"
                class="ruang-obat-badge {{ $filterTipe === 'puskesmas' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Puskesmas
                <span class="ml-1 opacity-70">{{ $tipeCounts['puskesmas'] ?? 0 }}</span>
            </button>
            <button wire:click="filterByTipe('pustu')" type="button"
                class="ruang-obat-badge {{ $filterTipe === 'pustu' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Pustu
                <span class="ml-1 opacity-70">{{ $tipeCounts['pustu'] ?? 0 }}</span>
            </button>
        </div>

        <select wire:model.live="filterStatus" class="ruang-obat-inline-select">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif ({{ $statusCounts['aktif'] ?? 0 }})</option>
            <option value="nonaktif">Nonaktif ({{ $statusCounts['nonaktif'] ?? 0 }})</option>
        </select>
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
