<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="ruang-obat-search-input-icon">
                <x-heroicon-m-magnifying-glass class="" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, nama generik, kategori..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="ruang-obat-filter-row">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Kategori VEN: </span>
            <button wire:click="filterByVen(null)" type="button"
                class="ruang-obat-badge {{ is_null($filterVen) ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Semua
            </button>
            <button wire:click="filterByVen('V')" type="button"
                class="ruang-obat-badge {{ $filterVen === 'V' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Vital
                <span class="ml-1 opacity-70">{{ $venCounts['V'] ?? 0 }}</span>
            </button>
            <button wire:click="filterByVen('E')" type="button"
                class="ruang-obat-badge {{ $filterVen === 'E' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Esensial
                <span class="ml-1 opacity-70">{{ $venCounts['E'] ?? 0 }}</span>
            </button>
            <button wire:click="filterByVen('N')" type="button"
                class="ruang-obat-badge {{ $filterVen === 'N' ? 'ruang-obat-badge-active' : 'ruang-obat-badge-inactive' }}">
                Non-Esensial
                <span class="ml-1 opacity-70">{{ $venCounts['N'] ?? 0 }}</span>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                @php
                    $filterCount = 0;
                    if (filled($filterStatus)) {
                        $filterCount++;
                    }
                    if (filled($filterKategori)) {
                        $filterCount++;
                    }
                    if (filled($filterBentuk)) {
                        $filterCount++;
                    }
                    if (filled($filterMetode)) {
                        $filterCount++;
                    }
                @endphp

                <button @click="open = !open" type="button" class="ruang-obat-filter-btn !w-auto">
                    <x-icon name="bx-filter" class="w-4 h-4 text-gray-500" />
                    Filter
                    @if ($filterCount > 0)
                        <span class="ruang-obat-filter-badge">{{ $filterCount }}</span>
                    @endif
                    <x-heroicon-m-chevron-down class="h-3.5 w-3.5" />
                </button>

                <div x-show="open" @click.away="open = false" x-cloak class="ruang-obat-filter-dropdown">
                <div class="space-y-3">
                    <div>
                        <label class="ruang-obat-filter-label">Status</label>
                        <select wire:model.live="filterStatus" class="ruang-obat-filter-select">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div>
                        <label class="ruang-obat-filter-label">Kategori</label>
                        <select wire:model.live="filterKategori" class="ruang-obat-filter-select">
                            <option value="">Semua Kategori</option>
                            @foreach ($kategoriOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="ruang-obat-filter-label">Bentuk Sediaan</label>
                        <select wire:model.live="filterBentuk" class="ruang-obat-filter-select">
                            <option value="">Semua Bentuk</option>
                            @foreach ($bentukOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="ruang-obat-filter-label">Metode Stok</label>
                        <select wire:model.live="filterMetode" class="ruang-obat-filter-select">
                            <option value="">Semua Metode</option>
                            @foreach ($metodeOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($filterCount > 0)
                        <button wire:click="resetFilters" @click="open = false" type="button" class="ruang-obat-filter-reset">
                            Reset Filter
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ route('admin.obat.cetak-xls', [
            'search' => $search,
            'ven' => $filterVen,
            'status' => $filterStatus,
            'kategori' => $filterKategori,
            'bentuk' => $filterBentuk,
            'metode' => $filterMetode,
        ]) }}" type="button" class="ruang-obat-filter-btn !w-auto">
            <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
            Export Excel
        </a>
    </div>
</div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
