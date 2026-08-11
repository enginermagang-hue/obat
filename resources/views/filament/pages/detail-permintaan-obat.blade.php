<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main Content (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Detail Obat (Filament Table) --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="px-6 pt-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Detail Obat yang Diminta
                    </h3>
                </div>
                <div class="overflow-x-auto p-0">
                    {{ $this->table }}
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Info Permintaan --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Permintaan
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">No. Permintaan</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->nomor_permintaan ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->tanggal_permintaan?->format('d/m/Y') ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Pengirim</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasPengirim?->nama ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Tujuan</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->fasilitasTujuan?->nama ?? 'Dinas Kesehatan' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Status --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Info Status
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBg }}">
                                {{ $statusLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Tipe</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tipeBg }}">
                                {{ $tipeLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Disetujui Oleh</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $record->disetujuiOleh?->name ?? 'Belum disetujui' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Surat Permintaan --}}
            @if (filled($record->surat_permintaan) && $record->status === 'menunggu_persetujuan')
                @php
                    $canViewSurat = auth()->user()->hasRole(['super_admin', 'admin_dinas', 'puskesmas']);
                @endphp
                @if ($canViewSurat)
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Surat Permintaan
                        </h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.permintaan.download-surat', $record) }}"
                               target="_blank"
                               class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                <x-heroicon-o-document-text class="h-8 w-8 text-primary-500" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-900 dark:text-white">
                                        {{ basename($record->surat_permintaan) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Klik untuk melihat surat
                                    </p>
                                </div>
                            </a>
                            <a href="{{ route('admin.permintaan.download-surat', $record) }}"
                               class="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                Download Surat
                            </a>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Distribusi Terkait --}}
            @if ($hasDistribusi)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Distribusi Terkait
                    </h3>
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($distribusi as $d)
                            <div class="flex flex-wrap items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $d->nomor_surat_jalan }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $d->tanggal_kirim?->format('d/m/Y') ?? '-' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ match ($d->status) {
                                            'dalam_pengiriman' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            'diterima' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'ditolak' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                        } }}">
                                        {{ match ($d->status) {
                                            'draft' => 'Draft',
                                            'dalam_pengiriman' => 'Dikirim',
                                            'diterima' => 'Diterima',
                                            'ditolak' => 'Ditolak',
                                            default => ucfirst($d->status),
                                        } }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
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

                    @if ($record->tanggal_disetujui)
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Disetujui</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_disetujui?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($record->tanggal_dikirim)
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                                <x-heroicon-m-paper-airplane class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Dikirim</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_dikirim?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($record->tanggal_diterima)
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                <x-heroicon-m-inbox-arrow-down class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Diterima</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_diterima?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($record->tanggal_ditolak)
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <x-heroicon-m-x-circle class="h-3.5 w-3.5 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Ditolak</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->tanggal_ditolak?->format('d M Y') ?? '-' }}
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

            {{-- Catatan --}}
            @if ($showCatatan)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Catatan
                    </h3>
                    <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                        {{ $record->catatan }}
                    </p>
                </div>
            @endif

            {{-- Alasan Penolakan --}}
            @if ($showAlasanPenolakan)
                <div class="rounded-xl border-2 border-red-200 bg-red-50 p-6 dark:border-red-900/50 dark:bg-red-900/10">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-x-circle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">Alasan Penolakan</h3>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-300 whitespace-pre-wrap">{{ $record->alasan_penolakan }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
