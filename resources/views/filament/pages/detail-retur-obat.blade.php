<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main Content (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Detail Obat (Filament Table) --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between px-6 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Detail Obat
                    </h3>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                        {{ $record->details->count() }} item
                    </span>
                </div>
                <div class="overflow-x-auto p-0">
                    {{ $this->table }}
                </div>
            </div>

            {{-- Catatan --}}
            @if (filled($record->catatan))
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Catatan
                    </h3>
                    <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                        {{ $record->catatan }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Info Retur --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Retur
                </h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Alasan Retur</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $alasanBg }}">
                                {{ $alasanLabel }}
                            </span>
                        </dd>
                    </div>
                    @if (filled($record->alasan_lainnya))
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Keterangan Alasan</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                                {{ $record->alasan_lainnya }}
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal Retur</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-white">
                            {{ $record->tanggal_retur?->format('d M Y') ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Faskes --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Fasilitas
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Pengirim</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasPengirim?->nama ?? 'Gudang Dinas' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Penerima</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasPenerima?->nama ?? '-' }}
                        </dd>
                    </div>
                    @if (filled($record->supplier_id))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Supplier</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">
                                {{ $record->supplier?->nama ?? '-' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Info Referensi --}}
            @if ($showDistribusi || $showPenerimaan)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Referensi
                    </h3>
                    <dl class="space-y-2 text-sm">
                        @if ($showDistribusi)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Distribusi</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">
                                    {{ $record->distribusi?->nomor_surat_jalan ?? '-' }}
                                </dd>
                            </div>
                        @endif
                        @if ($showPenerimaan)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Penerimaan</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">
                                    {{ $record->penerimaan?->nomor_penerimaan ?? '-' }}
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

                    @if (filled($record->tanggal_disetujui))
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Disetujui</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_disetujui?->format('d M Y, H:i') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if (filled($record->tanggal_dikirim))
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                <x-heroicon-m-truck class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Dikirim</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_dikirim?->format('d M Y, H:i') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if (filled($record->tanggal_diterima))
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <x-heroicon-m-archive-box-arrow-down class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Diterima</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_diterima?->format('d M Y, H:i') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($record->status === 'ditolak' && filled($record->tanggal_ditolak))
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <x-heroicon-m-x-circle class="h-3.5 w-3.5 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Ditolak</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_ditolak?->format('d M Y, H:i') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

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
