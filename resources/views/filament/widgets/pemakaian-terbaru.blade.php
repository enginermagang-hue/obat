<div class="fi-wi-widget col-span-full">
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Pemakaian Terbaru
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                5 pemakaian obat terakhir
            </p>
        </div>

        @if ($pemakaians->isEmpty())
            <div class="flex items-center justify-center py-8">
                <p class="text-sm text-gray-400 dark:text-gray-500">
                    Belum ada pemakaian obat.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Nomor</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Pasien</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Pelayanan</th>
                            <th class="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($pemakaians as $item)
                            <tr>
                                <td class="py-2.5 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item->nomor_pemakaian }}
                                </td>
                                <td class="py-2.5 text-gray-600 dark:text-gray-300">
                                    {{ Str::limit($item->nama_pasien ?? '-', 20) }}
                                </td>
                                <td class="py-2.5">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $item->jenis_pelayanan_label }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right text-gray-500 dark:text-gray-400">
                                    {{ $item->tanggal_pemakaian?->format('d/m/Y') ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
