<div class="fi-wi-widget col-span-full">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Aktivitas Terakhir
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                8 aktivitas terbaru di sistem
            </p>
        </div>

        @if ($activities->isEmpty())
            <div class="flex items-center justify-center py-8">
                <p class="text-sm text-gray-400 dark:text-gray-500">
                    Belum ada aktivitas.
                </p>
            </div>
        @else
            <div class="flow-root">
                <ul class="-mb-8 divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach ($activities as $activity)
                        @php
                            $eventColors = [
                                'created' => 'bg-emerald-500',
                                'updated' => 'bg-blue-500',
                                'deleted' => 'bg-red-500',
                                'approved' => 'bg-green-500',
                                'rejected' => 'bg-red-500',
                                'received' => 'bg-blue-500',
                                'completed' => 'bg-purple-500',
                                'login' => 'bg-amber-500',
                                'logout' => 'bg-gray-400',
                                'generated' => 'bg-indigo-500',
                            ];
                            $dotColor = $eventColors[$activity->event] ?? 'bg-gray-400';
                        @endphp
                        <li class="relative pb-8">
                            @if (! $loop->last)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start gap-3">
                                <div class="relative">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $dotColor }} ring-4 ring-white dark:ring-gray-800">
                                        <x-heroicon-m-check class="h-4 w-4 text-white" />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div>
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ $activity->description }}
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $activity->causer?->name ?? 'System' }}
                                            &middot;
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
