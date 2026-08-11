@if ($showSumberDanaAlert)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition
        class=""
    >
        <div
            class="flex items-start gap-3 rounded-lg border-s-4 border-amber-400 bg-amber-50 p-4 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <svg
                class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.96.871-1.026 2.286-.267 3.382.76 1.096 2.093 1.37 3.303.76L12 19.549l6.267 3.069c1.21.61 2.543.336 3.303-.76.76-1.096.693-2.511-.267-3.382L12 13.5 2.697 9.625z"
                />
            </svg>

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
