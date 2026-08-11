<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="restore">
            {{ $this->form }}

            <div class="flex gap-4 justify-start mt-6">
                <x-filament::button
                    type="submit"
                    color="danger"
                    icon="heroicon-o-arrow-uturn-left"
                    wire:confirm="PERINGATAN: Restore akan menghapus semua data saat ini dan menggantinya dengan data dari backup. Apakah Anda yakin?"
                >
                    Restore Database
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
