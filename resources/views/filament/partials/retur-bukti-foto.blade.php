@php
    $foto = $getRecord()->bukti_foto ?? null;
@endphp

@if (filled($foto))
    <a href="{{ Storage::url($foto) }}" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
        <x-heroicon-m-photo class="h-4 w-4 inline" />
        Lihat
    </a>
@else
    <span class="text-gray-400 dark:text-gray-500">-</span>
@endif
