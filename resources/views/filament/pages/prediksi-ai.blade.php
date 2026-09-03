<x-filament-panels::page>
    @php $stats = $this->getStats(); $alerts = $this->getCriticalAlerts(); $chart = $this->getChartData(); $models = $this->getModelRecords(); @endphp

    <div class="space-y-6">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-sm font-semibold">Filter</h3>
            {{ $this->form }}
            <p class="mt-2 text-xs text-gray-400">Filter Fasilitas/Obat mempengaruhi Hasil Prediksi, Model (per faskes+obat), dan Peringatan.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><div class="text-sm text-gray-500">Model Aktif (ANN)</div><div class="text-2xl font-bold">{{ $stats['model_aktif'] }}</div><div class="text-xs text-gray-400">per faskes+obat</div></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><div class="text-sm text-gray-500">Obat Diprediksi</div><div class="text-2xl font-bold">{{ $stats['obat_diprediksi'] }}</div><div class="text-xs text-gray-400">{{ \Carbon\Carbon::create()->month($this->bulan)->translatedFormat('F') }} {{ $this->tahun }}</div></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><div class="text-sm text-gray-500">Rata Akurasi (R²)</div><div class="text-2xl font-bold">{{ $stats['rata_akurasi'] !== null ? number_format($stats['rata_akurasi']*100,1).'%' : 'N/A' }}</div></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><div class="text-sm text-gray-500">Rata MAE / MAPE</div><div class="text-lg font-bold">{{ $stats['rata_mae'] !== null ? number_format($stats['rata_mae'],1) : '—' }} <span class="text-xs font-normal text-gray-400">/ {{ $stats['rata_mape'] !== null ? number_format($stats['rata_mape'],1).'%' : '—' }}</span></div></div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><div class="text-sm text-gray-500">Faskes Terlatih</div><div class="text-2xl font-bold">{{ $stats['faskes_terlatih'] }}</div></div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 font-semibold">Tren Prediksi (6 periode)</h3>
            <canvas id="prediksiChart" height="80"></canvas>
            <script>
                (function(){
                    const labels = @js($chart['labels']);
                    const values = @js($chart['values']);
                    const c = document.getElementById('prediksiChart');
                    if(!c || !window.Chart){ return; }
                    new Chart(c, {type:'line', data:{labels, datasets:[{label:'Total Prediksi', data:values, borderColor:'#067D9B', backgroundColor:'rgba(6,125,155,0.15)', fill:true, tension:0.3}]}, options:{responsive:true, plugins:{legend:{display:false}}}});
                })();
            </script>
        </div>

        @if($alerts->isNotEmpty())
        <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/30">
            <h3 class="mb-2 font-semibold text-amber-800 dark:text-amber-200">Peringatan Stok Kritis (Top 5)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Fasilitas</th><th>Obat</th><th>Prediksi</th><th>Stok</th><th>Kekurangan</th></tr></thead>
                <tbody>@foreach($alerts as $a)<tr class="border-t"><td>{{ $a->fasilitas->nama ?? '-' }}</td><td>{{ $a->obat->nama_obat ?? '-' }}</td><td class="font-semibold text-red-600">{{ $a->jumlah_prediksi }}</td><td>{{ $a->stok_saat_ini }}</td><td><span class="rounded-full bg-red-100 px-2 py-0.5 text-red-700">{{ $a->kekurangan }}</span></td></tr>@endforeach</tbody></table>
            </div>
        </div>
        @endif

        @php $hasData = $this->getHasPrediksiData(); @endphp
        @if(!$hasData)
        <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/30">
            <p class="text-sm text-amber-800 dark:text-amber-200">Belum ada data prediksi. Jalankan <code class="rounded bg-amber-100 px-1.5 py-0.5">php artisan ai:train-models --force</code> untuk generate prediksi ANN per faskes+obat.</p>
        </div>
        @endif

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-4 border-b"><h3 class="font-semibold">Hasil Prediksi — Per Obat</h3><p class="text-sm text-gray-500">1 baris = 1 obat × 1 periode. Filter Bulan/Tahun otomatis ke periode terbaru jika ada data (prediksi 3 bulan ke depan).</p></div>
            {{ $this->table }}
        </div>

        <div class="fi-table-card">
            <div class="fi-table-header p-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Model Prediksi — Per Faskes+Obat (ANN 9-12-8-1, 10 terbaru)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">1 baris per faskes+obat. File: ai-models/{fasilitas}_{obat}.json</p>
                </div>
                <x-filament::button size="sm" color="primary" wire:click="trainAll" wire:confirm="Latih ulang semua model per faskes+obat?">Train Semua</x-filament::button>
            </div>
            <div class="overflow-x-auto">
                <table class="fi-table w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5">Fasilitas</th>
                            <th class="px-4 py-2.5">Obat</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">R²</th>
                            <th class="px-4 py-2.5">MAE</th>
                            <th class="px-4 py-2.5">MAPE</th>
                            <th class="px-4 py-2.5">Tgl Training</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                        @forelse($models as $m)
                            @php $color = \App\Models\ModelPrediksi::getStatusColor($m->status); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $m->fasilitas->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $m->obat->nama_obat ?? '-' }}</td>
                                <td class="px-4 py-3"><x-filament::badge :color="$color">{{ $m->status }}</x-filament::badge></td>
                                <td class="px-4 py-3">{{ $m->akurasi_r2 !== null ? number_format((float) $m->akurasi_r2 * 100, 1).'%' : '—' }}</td>
                                <td class="px-4 py-3">{{ $m->mae !== null ? number_format((float) $m->mae, 1) : '—' }}</td>
                                <td class="px-4 py-3">{{ $m->mape !== null ? number_format((float) $m->mape, 1).'%' : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $m->tanggal_training?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><x-filament::button size="xs" color="gray" wire:click="trainModel({{ $m->id }})" wire:confirm="Latih ulang model faskes+obat ini?">Train Ulang</x-filament::button></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada model ANN per faskes+obat — jalankan <code>php artisan ai:train-models</code></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
