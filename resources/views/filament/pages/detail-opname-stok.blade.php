<x-filament-panels::page>
    {{-- ─── Header: Status + Tipe + Nomor ─── --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $statusBg }}">
                    {{ $statusLabel }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $tipeLabel }}
                </span>
            </div>
            <div class="text-right text-sm">
                <p class="font-semibold text-gray-900 dark:text-white">{{ $record->nomor_opname }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ $record->tanggal_opname?->format('d/m/Y') ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- ─── Grid 2 kolom: Info Utama & Status/Waktu ─── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Info Utama --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                Info Utama
            </h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Tipe Opname</dt>
                    <dd class="font-medium text-gray-900 dark:text-white text-right">
                        {{ $tipeLabel }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Fasilitas Kesehatan</dt>
                    <dd class="font-medium text-gray-900 dark:text-white text-right">
                        {{ $fasilitasLabel }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Petugas</dt>
                    <dd class="font-medium text-gray-900 dark:text-white text-right">
                        {{ $record->user?->name ?? '-' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Status & Waktu --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                Status & Waktu
            </h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Dibuat</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $record->created_at?->format('d/m/Y H:i') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Diubah</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $record->updated_at?->format('d/m/Y H:i') ?? '-' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ─── Detail Obat (Tabel) ─── --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                Detail Obat
                @if (count($details))
                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ count($details) }} item)
                    </span>
                @endif
            </h3>
        </div>
        <div class="overflow-x-auto border-t border-gray-200 dark:border-white/10">
            <table class="w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-6 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Obat</th>
                        <th class="px-4 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Batch</th>
                        <th class="px-4 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expired</th>
                        <th class="px-4 py-2.5 text-end text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Sistem</th>
                        <th class="px-4 py-2.5 text-end text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stok Fisik</th>
                        <th class="px-4 py-2.5 text-end text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selisih</th>
                        <th class="px-6 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($details as $item)
                        @php
                            $selisih = $item['selisih'] ?? 0;
                            $selisihColor = $selisih > 0
                                ? 'text-success-600 dark:text-success-400'
                                : ($selisih < 0
                                    ? 'text-danger-600 dark:text-danger-400'
                                    : 'text-gray-900 dark:text-white');
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $item['obat_name'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $item['batch_number'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $item['tanggal_expired'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-end tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($item['stok_sistem'] ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-end tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($item['stok_fisik'] ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-end tabular-nums font-medium {{ $selisihColor }}">
                                {{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item['keterangan'] ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada data obat
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Catatan ─── --}}
    @if (filled($record->catatan))
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Catatan</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $record->catatan }}</p>
        </div>
    @endif
</x-filament-panels::page>
