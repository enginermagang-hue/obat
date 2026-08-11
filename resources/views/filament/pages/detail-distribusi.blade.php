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

            {{-- Info Pengirim --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Pengirim
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Pengirim</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasPengirim?->nama ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Tipe</dt>
                        <dd class="text-right">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium {{ $tipeBg }}">
                                {{ $tipeLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Dikirim Oleh</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->pengirim?->name ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Penerima --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Penerima
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Penerima</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasPenerima?->nama ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Diterima Oleh</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->penerima?->name ?? 'Belum diterima' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Permintaan --}}
            @if (filled($record->permintaan_id))
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Info Permintaan
                    </h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">No. Permintaan</dt>
                            <dd class="text-right">
                                <a href="{{ $permintaanUrl }}"
                                   target="_blank"
                                   class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ $record->permintaan?->nomor_permintaan ?? '-' }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="text-right">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ ucfirst($record->permintaan?->status ?? '-') }}
                                </span>
                            </dd>
                        </div>
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
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <x-heroicon-m-paper-airplane class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Dikirim</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $record->tanggal_kirim?->format('d M Y') ?? 'Belum dikirim' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Diterima</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $record->tanggal_terima?->format('d M Y') ?? 'Belum diterima' }}
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
