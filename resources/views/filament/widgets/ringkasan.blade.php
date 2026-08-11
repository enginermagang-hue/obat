<div class="fi-wi-widget col-span-full">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Ringkasan
            </h3>
        </div>

        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6">
                <button type="button"
                    wire:click="$set('activeTab', 'permintaan')"
                    class="whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors duration-200
                        {{ $activeTab === 'permintaan' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300' }}">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6" />
                        </svg>
                        Permintaan Terbaru
                    </span>
                </button>

                @if (in_array('stok', $this->getVisibleTabs()))
                    <button type="button"
                        wire:click="$set('activeTab', 'stok')"
                        class="whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors duration-200
                            {{ $activeTab === 'stok' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300' }}">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Stok Menipis
                        </span>
                    </button>
                @endif

                @if (in_array('batch', $this->getVisibleTabs()))
                    <button type="button"
                        wire:click="$set('activeTab', 'batch')"
                        class="whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors duration-200
                            {{ $activeTab === 'batch' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300' }}">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Batch Akan Expired
                        </span>
                    </button>
                @endif
            </nav>
        </div>

        @if ($activeTab === 'permintaan')
            @if ($permintaans->isEmpty())
                <div class="flex items-center justify-center py-8">
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        Belum ada permintaan obat.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-400">
                                <th class="pb-2 font-bold text-gray-700 dark:text-gray-400">Nomor</th>
                                <th class="pb-2 font-bold text-gray-700 dark:text-gray-400">Dari</th>
                                <th class="pb-2 font-bold text-gray-700 dark:text-gray-400">Ke</th>
                                <th class="pb-2 font-bold text-gray-700 dark:text-gray-400">Status</th>
                                <th class="pb-2 text-right font-bold text-gray-500 dark:text-gray-400">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach ($permintaans as $item)
                                <tr>
                                    <td class="py-2.5 font-medium text-gray-900 dark:text-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="p-2 bg-blue-100 rounded-full border border-blue-200">
                                                <x-icon name="bx-file" class="text-blue-700 w-4 h-4" />
                                            </div>
                                            {{ $item->nomor_permintaan }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 text-gray-600 dark:text-gray-300">
                                        {{ Str::limit($item->fasilitasPengirim?->nama ?? '-', 20) }}
                                    </td>
                                    <td class="py-2.5 text-gray-600 dark:text-gray-300">
                                        {{ Str::limit($item->fasilitasTujuan?->nama ?? '-', 20) }}
                                    </td>
                                    <td class="py-2.5">
                                        @php
                                            $statusColors = [
                                                'menunggu_persetujuan' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'disetujui' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'diterima' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                            $statusLabels = [
                                                'menunggu_persetujuan' => 'Pending',
                                                'disetujui' => 'Disetujui',
                                                'diterima' => 'Diterima',
                                                'ditolak' => 'Ditolak',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right text-gray-500 dark:text-gray-400">
                                        {{ $item->tanggal_permintaan?->format('d/m/Y') ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        @if ($activeTab === 'stok')
            @if ($items->isEmpty())
                <div class="flex items-center justify-center py-8">
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        Semua stok dalam kondisi aman.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 font-bold text-gray-700 dark:text-gray-400">Obat</th>
                                <th class="pb-2 text-right font-bold text-gray-700 dark:text-gray-400">Stok</th>
                                <th class="pb-2 text-right font-bold text-gray-700 dark:text-gray-400">Minimum</th>
                                <th class="pb-2 text-right font-bolf text-gray-700 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach ($items as $item)
                                <tr>
                                    <td class="py-2.5 text-gray-900 dark:text-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="p-2 bg-blue-100 rounded-full border border-blue-200">
                                                <x-icon name="bx-box" class="text-blue-700 w-4 h-4" />
                                            </div>
                                            {{ $item->obat->nama_obat ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 text-right font-medium text-gray-900 dark:text-gray-100">
                                        {{ number_format($item->jumlah) }}
                                    </td>
                                    <td class="py-2.5 text-right text-gray-500 dark:text-gray-400">
                                        {{ number_format($item->stok_minimum) }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        @if ($item->jumlah === 0)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                Kritis
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                Menipis
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        @if ($activeTab === 'batch')
            @if ($batches->isEmpty())
                <div class="flex items-center justify-center py-8">
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        Tidak ada batch yang akan expired.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 font-medium text-gray-700 dark:text-gray-400">Obat</th>
                                <th class="pb-2 font-medium text-gray-700 dark:text-gray-400">Batch</th>
                                <th class="pb-2 text-right font-medium text-gray-700 dark:text-gray-400">Expired</th>
                                <th class="pb-2 text-right font-medium text-gray-700 dark:text-gray-400">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach ($batches as $batch)
                                @php
                                    $daysLeft = (int) now()->diffInDays($batch->tanggal_expired, false);
                                    $daysLeft = max(0, $daysLeft);

                                    $bgColor = $daysLeft <= 7 ? 'bg-red-50 dark:bg-red-900/30' : ($daysLeft <= 14 ? 'bg-amber-50 dark:bg-amber-900/30' : 'bg-gray-50 dark:bg-gray-700');
                                    $borderColor = $daysLeft <= 7 ? 'border-red-200 dark:border-red-800' : ($daysLeft <= 14 ? 'border-amber-200 dark:border-amber-800' : 'border-gray-200 dark:border-gray-700');
                                    $iconColor = $daysLeft <= 7 ? 'text-red-700 dark:text-red-400' : ($daysLeft <= 14 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-700 dark:text-gray-300');
                                @endphp
                                <tr>
                                    <td class="py-2.5 text-gray-900 dark:text-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="p-2 {{ $bgColor }} rounded-full border {{ $borderColor }}">
                                                <x-icon name="bx-clock" class="{{ $iconColor }} w-4 h-4" />
                                            </div>
                                            {{ $batch->obat->nama_obat ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">
                                        {{ $batch->batch_number }}
                                    </td>
                                    <td class="py-2.5 text-right text-gray-500 dark:text-gray-400">
                                        {{ $batch->tanggal_expired->format('d/m/Y') }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        @if ($daysLeft <= 7)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $daysLeft }} hari
                                            </span>
                                        @elseif ($daysLeft <= 14)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                {{ $daysLeft }} hari
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $daysLeft }} hari
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
