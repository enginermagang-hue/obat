<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main Content (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Detail Obat (Filament Table) --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="px-6 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Detail Obat
                    </h3>
                </div>
                <div class="overflow-x-auto p-0">
                    {{ $this->table }}
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Info Pemakaian --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Pemakaian
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">No. Pemakaian</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->nomor_pemakaian ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->tanggal_pemakaian?->format('d/m/Y') ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Fasilitas</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitas?->nama ?? 'Gudang' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Jenis Pelayanan</dt>
                        <dd class="text-right">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $jenisBg }}-100 text-{{ $jenisBg }}-700 dark:bg-{{ $jenisBg }}-900/30 dark:text-{{ $jenisBg }}-400">
                                {{ $jenisLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Petugas</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->user?->name ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Pasien --}}
            @if ($showDataPasien)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Info Pasien
                    </h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Nama Pasien</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">
                                {{ $record->nama_pasien ?? '-' }}
                            </dd>
                        </div>
                        @if (filled($record->no_rekam_medis))
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">No. Rekam Medis</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">
                                    {{ $record->no_rekam_medis }}
                                </dd>
                            </div>
                        @endif
                        @if (filled($record->catatan))
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Catatan</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">
                                    {{ $record->catatan }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Timeline
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <x-heroicon-m-document-text class="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Dibuat</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $record->created_at?->format('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <x-heroicon-m-pencil class="h-3.5 w-3.5 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Terakhir Diubah</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $record->updated_at?->format('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
