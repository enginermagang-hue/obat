<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ open: false }" class="relative" @click.outside="open = false">
        <button @click="open = !open" type="button" class="ruang-obat-filter-btn">
            <x-heroicon-m-calendar class="h-4 w-4" />
            {{ $getBtnLabel() }}
            @if (filled(data_get($getState(), 'from')) || filled(data_get($getState(), 'to')))
                <span class="ruang-obat-filter-badge">
                    {{ collect([data_get($getState(), 'from'), data_get($getState(), 'to')])->filter()->count() }}
                </span>
            @endif
            <x-heroicon-m-chevron-down class="h-3.5 w-3.5 absolute end-2" />
        </button>

        <div x-show="open" x-cloak x-transition class="ruang-obat-filter-dropdown">
            <div class="space-y-3">
                <div>
                    <label class="ruang-obat-filter-label">Dari</label>
                    <input
                        type="date"
                        {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}.from"
                        class="ruang-obat-filter-select"
                    >
                </div>
                <div>
                    <label class="ruang-obat-filter-label">Sampai</label>
                    <input
                        type="date"
                        {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}.to"
                        class="ruang-obat-filter-select"
                    >
                </div>

                @if (filled(data_get($getState(), 'from')) || filled(data_get($getState(), 'to')))
                    <button
                        type="button"
                        wire:click="$set('{{ $getStatePath() }}', null)"
                        @click="open = false"
                        class="ruang-obat-filter-reset"
                    >
                        <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                        Reset Tanggal
                    </button>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>
