<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex items-center gap-4 mt-6">
        <x-filament::button wire:click="save">
            Simpan Preferensi
        </x-filament::button>
    </div>
</x-filament-panels::page>
