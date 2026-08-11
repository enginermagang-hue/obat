<div class="overflow-x-auto">
    <table class="w-full divide-y divide-gray-200 dark:divide-white/10">
        <thead>
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="px-3 py-3 text-start text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Nama Obat
                </th>
                <th class="px-3 py-3 text-end text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Diminta
                </th>
                <th class="px-3 py-3 text-end text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Disetujui
                </th>
                <th class="px-3 py-3 text-end text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Dikirim
                </th>
                <th class="px-3 py-3 text-end text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Diterima
                </th>
                <th class="px-3 py-3 text-start text-sm font-semibold text-gray-500 dark:text-gray-400">
                    Catatan
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @php
                $items = $getState();
            @endphp
            @forelse ($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="px-3 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $item['obat']['nama_obat'] ?? '-' }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ number_format($item['jumlah_diminta']) }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ $item['jumlah_disetujui'] ? number_format($item['jumlah_disetujui']) : '-' }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ $item['jumlah_dikirim'] ? number_format($item['jumlah_dikirim']) : '-' }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ $item['jumlah_diterima'] ? number_format($item['jumlah_diterima']) : '-' }}
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $item['catatan'] ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                        Tidak ada data obat
                    </td>
                </tr>
            @endforelse
        </tbody>
        @php
            $totalDiminta = collect($items)->sum(fn ($i) => (int) ($i['jumlah_diminta'] ?? 0));
            $totalDisetujui = collect($items)->sum(fn ($i) => (int) ($i['jumlah_disetujui'] ?? 0));
            $totalDikirim = collect($items)->sum(fn ($i) => (int) ($i['jumlah_dikirim'] ?? 0));
            $totalDiterima = collect($items)->sum(fn ($i) => (int) ($i['jumlah_diterima'] ?? 0));
        @endphp
        @if (count($items))
            <tfoot>
                <tr class="bg-gray-50 dark:bg-white/5 font-semibold">
                    <td class="px-3 py-3 text-sm text-gray-700 dark:text-gray-300">
                        Total
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ number_format($totalDiminta) }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ number_format($totalDisetujui) }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ number_format($totalDikirim) }}
                    </td>
                    <td class="px-3 py-3 text-sm text-end tabular-nums text-gray-700 dark:text-gray-300">
                        {{ number_format($totalDiterima) }}
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
