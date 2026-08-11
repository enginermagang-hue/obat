<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="space-y-2">
        <div class="flex items-center space-x-2">
            <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                Penerimaan: {{ $record->nomor_penerimaan }}
            </h1>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $statusBg }}">
                    {{ $statusLabel }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $tipeBg }}">
                    {{ $tipeLabel }}
                </span>
            </div>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Dibuat {{ $record->created_at?->format('d M Y, H:i') }} WIB
        </div>
    </div>

    @if ($actions)
        <div class="flex shrink-0 items-center gap-3">
            <x-filament::actions
                :actions="$actions"
                :alignment="$actionsAlignment"
            />
        </div>
    @endif
</div>
