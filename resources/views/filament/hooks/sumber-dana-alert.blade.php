@if (session('sumber_dana_alert'))
<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    class="mx-auto my-4 max-w-7xl"
>
    <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
        
            <x-icon name="bx-alert-triangle" class="mt-0.5 flex-shrink-0 text-amber-500 w-5 h-5" />

            <div class="flex-1">
                <p class="text-sm font-bold">Sumber Dana Belum Dibuat</p>
                <p class="mt-1 text-sm">
                    Silakan buat sumber dana yang aktif terlebih dahulu sebelum melakukan penerimaan obat.
                </p>
                <div class="mt-3">
                    <a
                        href="{{ route('filament.admin.resources.sumber-dana.index') }}"
                        class="inline-flex items-center rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600"
                    >
                        <svg
                            class="mr-1 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>

                        Buat Sumber Dana
                    </a>
                </div>
            </div>

            <button
                type="button"
                x-on:click="show = false"
                class="text-amber-400 hover:text-amber-600 dark:text-amber-300 dark:hover:text-amber-100"
                aria-label="Tutup"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
</div>
@endif
