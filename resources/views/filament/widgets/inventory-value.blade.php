<div class="fi-wi-widget col-span-full">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Nilai Inventaris
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Total nilai stok obat tersedia
            </p>
        </div>

        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $totalValue }}
            </p>
            <div class="mt-2 flex items-center gap-4">
                <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-cube class="h-4 w-4" />
                    {{ number_format($totalObat) }} obat
                </span>
                <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-archive-box class="h-4 w-4" />
                    {{ number_format($totalBatch) }} batch
                </span>
            </div>
        </div>
    </div>
</div>
