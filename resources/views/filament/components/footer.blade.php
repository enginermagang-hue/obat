@php
    $appName = config('app.name');
    $version = config('app.version');
    $year = date('Y');
@endphp

<footer
    class="fi-footer border-t border-gray-200 bg-white px-6 py-3 dark:border-gray-800 dark:bg-gray-900"
>
    <div class="mx-auto flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
        <span>&copy; {{ $year }} {{ $appName }}. Hak cipta dilindungi.</span>
        <span>v{{ $version }}</span>
    </div>
</footer>
