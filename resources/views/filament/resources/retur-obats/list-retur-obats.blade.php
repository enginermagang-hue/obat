<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Daftar Retur Obat
        </x-slot>
        {{ $this->table }}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Inspeksi Retur
        </x-slot>
        <x-slot name="description">
            Hasil pemeriksaan obat retur
        </x-slot>
        @livewire(App\Filament\Components\ListInspeksiReturTable::class)
    </x-filament::section>
</x-filament-panels::page>
