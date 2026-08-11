<div class="fi-wi-widget col-span-full h-full">
    <div class="flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="flex items-center gap-2.5 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <x-heroicon-o-information-circle class="h-4 w-4" />
            </div>
            <h3 class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white">
                Informasi Umum
            </h3>
        </div>

        <div class="flex-1">
            <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="flex flex-col items-center">
                    <div class="text-sm my-3 text-gray-500 dark:text-gray-400">Suppliers</div>
                    <div class="flex items-center p-2 pb-6 gap-4">
                        <div class="flex h-13 w-13 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                            <x-heroicon-o-user-group class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ number_format($totalSuppliers) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center">
                    <div class="text-sm my-3 text-gray-500 dark:text-gray-400">Fasilitas</div>
                    <div class="flex items-center p-2 pb-6 gap-4">
                        <div class="flex h-13 w-13 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-heroicon-o-building-office-2 class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ number_format($totalFasilitas) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center">
                    <div class="text-sm my-3 text-gray-500 dark:text-gray-400">Jumlah Obat</div>
                    <div class="flex items-center p-2 pb-6 gap-4">
                        <div class="flex h-13 w-13 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <x-heroicon-o-cube class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ number_format($totalObat) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-5 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white">
                        Aktivitas Bulan Ini
                    </h4>
                    <select
                        wire:model.live="selectedPeriod"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                        <option value="year">Tahun Ini</option>
                    </select>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="flex items-center gap-4">
                        <div class="relative flex h-30 w-30 flex-shrink-0 items-center justify-center">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="10"
                                    class="dark:stroke-gray-700" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#067D9B" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ ($this->ring1Percentage / 100) * 251.2 }} 251.2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ number_format($totalPenerimaan) }}
                            </p>
                            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">Penerimaan</span>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                    {{ $this->ring1Percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="relative flex h-30 w-30 flex-shrink-0 items-center justify-center">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="10"
                                    class="dark:stroke-gray-700" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#f59e0b" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ ($this->ring2Percentage / 100) * 251.2 }} 251.2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ number_format($totalPermintaan) }}
                            </p>
                            <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Permintaan</span>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                                    {{ $this->ring2Percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
