<div class="fi-wi-widget col-span-full">
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="flex items-stretch divide-x divide-gray-200 dark:divide-gray-700">
            @foreach ($stats as $stat)
                @php
                    $iconContainer = match ($stat['color']) {
                        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
                        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'danger' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
                        default => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp
                <div class="flex-1 p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconContainer }}">
                            <x-dynamic-component
                                :component="$stat['icon']"
                                class="h-5 w-5"
                            />
                        </div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </span>
                    </div>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $stat['value'] }}
                    </p>

                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-sm font-semibold {{ $stat['changeColor'] }}">
                            {{ $stat['change'] }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $stat['description'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
