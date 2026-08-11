<div class="flex flex-col gap-0.5 py-2">
    <div class="flex items-center gap-2">
        <span class="font-medium text-gray-950 dark:text-white">{{ $record->nama_obat }}</span>
    </div>

    @if ($record->kode_obat || $record->nama_generik)
        <div class="flex items-center justify-start gap-1.5">

            @if ($record->kode_obat)
                <span
                    class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    {{ $record->kode_obat }}
                </span>
            @endif

            @if ($record->nama_generik)
                <span class="text-xs italic text-gray-500 dark:text-gray-400">{{ $record->nama_generik }}</span>
            @endif
        </div>
    @endif

    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
        @if ($record->kategori)
            <span>{{ $record->kategori }}</span>
        @endif

        @if ($record->kategori && $record->satuan)
            <span class="text-gray-300 dark:text-gray-600">•</span>
        @endif

        @if ($record->satuan)
            <span>{{ $record->satuan }}</span>
        @endif
    </div>
</div>