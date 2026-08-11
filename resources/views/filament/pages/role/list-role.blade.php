<x-filament-panels::page>
    <div class="py-3">
        <div class="relative w-full">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-m-magnifying-glass class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama role..."
                class="ruang-obat-search-input">
        </div>
    </div>

    <div class="fi-table-card">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
