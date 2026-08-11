<x-filament-panels::page>
    {{ $this->schema }}

    {{ $this->table }}

    {{-- ─── Catatan ─── --}}
    @if (filled($record->catatan))
        <div class="rounded-xl bg-white px-6 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-start gap-2 text-sm">
                <span class="shrink-0 font-medium text-gray-500 dark:text-gray-400">Catatan:</span>
                <span class="text-gray-700 dark:text-gray-300">{{ $record->catatan }}</span>
            </div>
        </div>
    @endif
</x-filament-panels::page>
