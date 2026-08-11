<div class="fi-wi-widget col-span-full h-full">
    <div class="flex h-full flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Penerimaan & Permintaan
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Grafik penerimaan dan permintaan obat
                </p>
            </div>
            <div class="flex gap-1 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-700">
                @foreach ($this->getFilters() as $value => $label)
                    <button
                        wire:click="$set('filter', '{{ $value }}')"
                        @class([
                            'rounded-md px-3 py-1 text-xs font-medium transition-colors',
                            'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white' => $filter === $value,
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $filter !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div wire:key="chart-{{ $filter }}" class="relative flex-1" style="min-height: 300px;">
            <div
                x-data="{
                    chart: null,
                    options: {{ Js::from($chartOptions) }},
                    init() {
                        const canvas = this.$refs.canvas;
                        window.Chart.getChart(canvas)?.destroy();
                        this.chart = new window.Chart(canvas, this.options);
                    },
                    destroy() {
                        this.chart?.destroy();
                        this.chart = null;
                    },
                }"
                x-init="init"
                x-destroy="destroy"
                class="h-full w-full"
            >
                <canvas x-ref="canvas" class="h-full w-full"></canvas>
            </div>
        </div>
    </div>
</div>
