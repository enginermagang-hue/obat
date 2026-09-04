<x-filament-panels::page>
    @php
        $rows = $this->getRekomendasiRows();
        $kpi = $this->getKpi();
        $insight = $this->getInsightAi();
        $lonjakan = $this->getLonjakan();
        $models = $this->getModelRecords();
        $hasData = $this->getHasPrediksiData();
        $horizon = (int) $this->horizon;
        $fasilitas = $this->getFasilitasNama();
        $periodeLabel = $this->bulan && $this->tahun ? \Carbon\Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y') : '';

        $perPage = 10;
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $pageId = min(max(1, (int) $this->page), $totalPages);
        $items = collect($rows)->forPage($pageId, $perPage);
        $pageStart = ($pageId - 1) * $perPage;

        $fmtIdr = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
        $anggaran = $kpi['estimasi_anggaran'] >= 1_000_000
            ? 'Rp ' . number_format($kpi['estimasi_anggaran'] / 1_000_000, 1, ',', '.') . ' Jt'
            : $fmtIdr($kpi['estimasi_anggaran']);
    @endphp

    <div class="space-y-4">
    {{-- ═══════════ Header + Filter ═══════════ --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Prediksi Kebutuhan Obat</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Prediksi berbasis AI dari data historis, musim, dan tren epidemiologi</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span>Gudang Dinas - Puskesmas</span>
                @if($kpi['akurasi_model'] > 0)
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Sangat Baik</span>
                @endif
            </div>
        </div>

        <div class="mt-3">{{ $this->form }}</div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Periode:</span>
                @foreach([1, 3, 6, 12] as $h)
                    <button type="button" wire:click="setHorizon({{ $h }})"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $horizon === $h ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10' }}">
                        {{ $h }} Bulan
                    </button>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                @unless($hasData)
                    <span class="rounded bg-amber-100 px-2 py-1 text-xs text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">Jalankan ai:train-models</span>
                @endunless
                <a href="{{ $this->exportUrl() }}" target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-success-700">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                    Export Excel
                </a>
                @if($this->getCanBuatPo())
                    <button type="button" wire:click="buatPo"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-700">
                        <x-filament::icon icon="heroicon-o-document-plus" class="h-4 w-4" />
                        Buat PO Otomatis
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════ KPI ═══════════ --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Obat Diprediksi</div>
            <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpi['obat_diprediksi'] }}<span class="text-sm font-normal text-gray-400"> / {{ $kpi['total_obat_aktif'] }} obat</span></div>
            <div class="mt-1 text-xs text-gray-400">{{ $periodeLabel }} • {{ $horizon }} bulan ke depan</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Obat Defisit Potensial</div>
            <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $kpi['obat_defisit'] }}<span class="text-sm font-normal text-gray-400"> item</span></div>
            <div class="mt-1 text-xs text-gray-400">Risiko kekurangan stok &lt;21 hari</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Estimasi Anggaran</div>
            <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $anggaran }}</div>
            <div class="mt-1 text-xs text-gray-400">Nilai rekomendasi pesan</div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Akurasi Model AI</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($kpi['akurasi_model'] * 100, 1) }}%</div>
            <div class="mt-1 text-xs text-gray-400">Confidence rata-rata item</div>
        </div>
    </div>

    {{-- ═══════════ Insight AI ═══════════ --}}
    @if($insight['defisit_count'] > 0)
        <div class="rounded-xl bg-primary-50 p-4 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/30">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                <span class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Insight AI Hari Ini • {{ strtoupper($insight['fasilitas']) }}</span>
            </div>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Model mendeteksi lonjakan kebutuhan <b>{{ $insight['primary'] }}</b>
                @if($insight['secondary'])
                    dan <b>{{ $insight['secondary'] }}</b>
                @endif
                untuk <b>{{ $insight['fasilitas'] }}</b> dalam <b>{{ $insight['horizon'] }} Bulan</b> ke depan. <b>{{ $insight['defisit_count'] }}</b> obat berisiko kekurangan stok dalam 21 hari. Prioritas: <b>{{ $insight['primary'] }}</b>.
            </p>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Confidence AI {{ $insight['confidence'] }}% • Diperbarui hari ini</p>
        </div>
    @else
        <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-500/30">
            <p class="text-sm text-emerald-700 dark:text-emerald-300">Semua obat dalam kondisi aman untuk periode {{ $periodeLabel }} — tidak ada rekomendasi pesan.</p>
        </div>
    @endif

    {{-- ═══════════ Chart: Realisasi vs Prediksi ═══════════ --}}
    @livewire(App\Filament\Widgets\PrediksiRealisasiChart::class)

    {{-- ═══════════ Top Kebutuhan ═══════════ --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top {{ count($lonjakan) }} Kebutuhan Tertinggi</h3>
        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Periode {{ $horizon }} Bulan • {{ $fasilitas }}</p>
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach($lonjakan as $r)
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $r['nama_obat'] }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ $r['kategori'] }} • {{ $r['status'] }}</div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-sm font-bold @if($r['rekom'] > 0) text-amber-600 dark:text-amber-400 @else text-gray-400 @endif">{{ number_format($r['rekom'], 0, ',', '.') }} {{ $r['satuan'] }}</div>
                        <div class="text-xs text-gray-400">rekomendasi pesan</div>
                    </div>
                </div>
            @endforeach
        </div>
        <a href="{{ route('filament.admin.pages.analisis-obat-page', array_filter(['fasilitas_id' => $this->fasilitas_id], fn ($v) => $v !== null)) }}"
            class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20">
            Lihat Analisis Lengkap
            <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
        </a>
    </div>

    {{-- ═══════════ Detail Prediksi (Tabel) ═══════════ --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="border-b border-gray-100 p-4 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Detail Prediksi {{ $horizon }} Bulan — {{ $fasilitas }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Rekomendasi = Prediksi - Stok + Safety Stock 20%</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2.5">No</th>
                        <th class="px-4 py-2.5">Obat</th>
                        <th class="px-4 py-2.5 text-right">Stok Saat Ini</th>
                        <th class="px-4 py-2.5 text-right">Rata²/Bulan</th>
                        <th class="px-4 py-2.5 text-right">Prediksi {{ $horizon }} Bln</th>
                        <th class="px-4 py-2.5 text-right">Rekom. Pesan</th>
                        <th class="px-4 py-2.5 text-center">Confidence</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse($items as $i => $r)
                        @php $no = $pageStart + $i + 1; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 text-gray-400">{{ $no }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $r['nama_obat'] }}</div>
                                <div class="text-xs text-gray-400">{{ $r['kategori'] }} @if($r['metode']) • {{ $r['metode'] }} @endif</div>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($r['stok'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($r['rata_per_bulan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($r['prediksi_horizon'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($r['rekom'] > 0)
                                    <span class="font-bold text-amber-600 dark:text-amber-400">+{{ number_format($r['rekom'], 0, ',', '.') }}</span>
                                    <span class="text-xs text-gray-400">{{ $r['satuan'] }}</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">Cukup</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $r['akurasi'] !== null ? number_format($r['akurasi'] * 100, 1).'%' : '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php $badgeClass = match($r['status_color']) {
                                    'danger' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                                    'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                    default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                                }; @endphp
                                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $r['status'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" wire:click="showDetail({{ $r['obat_id'] }})"
                                        class="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/20">
                                        Detail
                                    </button>
                                    @if($this->getCanBuatPo())
                                        <button type="button" wire:click="buatPoObat({{ $r['obat_id'] }})"
                                            class="rounded-md bg-primary-600 px-2 py-1 text-xs font-semibold text-white hover:bg-primary-700"
                                            {{ $r['rekom'] <= 0 ? 'disabled' : '' }}>
                                            + PO
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">
                                @if($hasData)
                                    Tidak ada data prediksi untuk filter ini.
                                @else
                                    Belum ada data prediksi. Jalankan <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/10">php artisan ai:train-models --force</code>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($totalPages > 1)
            <div class="flex items-center justify-between border-t border-gray-100 p-3 text-sm dark:border-white/10">
                <div class="text-xs text-gray-500 dark:text-gray-400">Menampilkan {{ count($items) }} dari {{ $total }} obat • {{ $fasilitas }} • {{ $horizon }} Bulan</div>
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="setPage({{ $pageId - 1 }})" {{ $pageId <= 1 ? 'disabled' : '' }} class="rounded px-2 py-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/10">‹</button>
                    @for($p = 1; $p <= $totalPages; $p++)
                        <button type="button" wire:click="setPage({{ $p }})"
                            class="rounded px-2.5 py-1 {{ $p === $pageId ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' }}">{{ $p }}</button>
                    @endfor
                    <button type="button" wire:click="setPage({{ $pageId + 1 }})" {{ $pageId >= $totalPages ? 'disabled' : '' }} class="rounded px-2 py-1 text-gray-500 hover:bg-gray-100 disabled:opacity-30 dark:hover:bg-white/10">›</button>
                </div>
            </div>
        @endif
    </div>

    {{-- ═══════════ Modal Detail per Obat ═══════════ --}}
    <x-filament::modal id="prediksi-detail" width="2xl">
        @php $detail = $this->getDetailData(); @endphp

        <x-slot name="heading">
            @if($detail)
                <div>
                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $detail['ringkasan']['nama_obat'] }}</div>
                    <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
                        {{ $detail['ringkasan']['kategori'] ?? '-' }}
                        • Confidence {{ $detail['ringkasan']['akurasi'] !== null ? number_format($detail['ringkasan']['akurasi'] * 100, 1).'%' : '—' }}
                        • Stok {{ number_format($detail['ringkasan']['stok'], 0, ',', '.') }}
                        • Avg {{ number_format($detail['ringkasan']['rata_per_bulan'], 0, ',', '.') }}/bln
                    </div>
                </div>
            @endif
        </x-slot>

        @if($detail)
            @php
                $rk = $detail['ringkasan'];
                $gud = $detail['stok_gudang'];
                $cukup = $rk['rekom'] > 0 && $gud >= $rk['rekom'];
            @endphp
            <div class="space-y-5">
                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tren Pemakaian 12 Bulan</h4>
                    <div class="flex h-28 items-end gap-1.5">
                        @foreach($detail['tren'] as $t)
                            <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1 self-stretch">
                                <div class="w-full rounded-t bg-emerald-500/70 dark:bg-emerald-400/60" style="height: {{ $detail['tren_max'] > 0 ? round($t['jumlah'] / $detail['tren_max'] * 100) : 0 }}%" title="{{ $t['label'] }}: {{ number_format($t['jumlah'], 0, ',', '.') }}"></div>
                                <span class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500">{{ $t['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Faktor yang Mempengaruhi AI</h4>
                    <ul class="space-y-2">
                        @foreach($detail['factors'] as $f)
                            <li class="flex items-start gap-2 text-sm">
                                <x-filament::icon icon="heroicon-m-sparkles" class="mt-0.5 h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400" />
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $f['judul'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $f['sub'] }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rekomendasi Distribusi</h4>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Gudang Dinas stok</div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ number_format($gud, 0, ',', '.') }} {{ $rk['satuan'] }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Kebutuhan (rekom)</div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ number_format($rk['rekom'], 0, ',', '.') }} {{ $rk['satuan'] }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Status</div>
                            <div class="font-bold {{ $rk['rekom'] <= 0 ? 'text-emerald-600 dark:text-emerald-400' : ($cukup ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400') }}">
                                {{ $rk['rekom'] <= 0 ? 'Tidak perlu' : ($cukup ? 'Tercukupi' : 'Perlu pengadaan') }}
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        @if($rk['rekom'] <= 0)
                            Stok mencukupi untuk horizon {{ $horizon }} bulan.
                        @elseif($cukup)
                            Dapat dipenuhi seluruhnya dari Gudang Dinas.
                        @else
                            Gudang mencakup {{ number_format($gud, 0, ',', '.') }} dari {{ number_format($rk['rekom'], 0, ',', '.') }} {{ $rk['satuan'] }}; sisanya via pengadaan baru.
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Perhitungan Rekomendasi ({{ $horizon }} Bulan)</h4>
                    <div class="grid grid-cols-3 gap-3 text-center text-sm">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Prediksi</div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ number_format($rk['prediksi_horizon'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Safety 20%</div>
                            <div class="font-bold text-gray-900 dark:text-white">+{{ number_format($detail['safety'], 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/10">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pesan</div>
                            <div class="font-bold text-primary-700 dark:text-primary-300">{{ number_format($rk['rekom'], 0, ',', '.') }} {{ $rk['satuan'] }}</div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Rincian bulanan:
                        @foreach($detail['bulanan'] as $i => $b){{ $i > 0 ? ' • ' : '' }}{{ $b['label'] }}: {{ number_format($b['jumlah'], 0, ',', '.') }}@if($b['lower'] !== null && $b['upper'] !== null) ({{ number_format($b['lower'], 0, ',', '.') }}–{{ number_format($b['upper'], 0, ',', '.') }})@endif@endforeach
                    </p>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            @if($detail)
                <div class="flex flex-wrap justify-end gap-2">
                    <x-filament::button color="gray" wire:click="closeDetail">Tutup</x-filament::button>
                    @if($this->getCanBuatPo())
                        <x-filament::button color="primary" wire:click="buatPoObat({{ $detail['ringkasan']['obat_id'] }})" :disabled="$detail['ringkasan']['rekom'] <= 0">Tambah ke PO</x-filament::button>
                        <x-filament::button color="success" wire:click="mintaDistribusi({{ $detail['ringkasan']['obat_id'] }})" :disabled="$detail['ringkasan']['rekom'] <= 0">Minta Distribusi</x-filament::button>
                    @endif
                </div>
            @endif
        </x-slot>
    </x-filament::modal>

    {{-- ═══════════ Model Training (collapse) ═══════════ --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <details>
            <summary class="cursor-pointer p-4 text-sm font-semibold text-gray-900 dark:text-white">Model Prediksi — per Faskes+Obat (AI, 10 terbaru)</summary>
            <div class="flex items-center justify-between px-4 pb-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">File: ai-models/{fasilitas}_{obat}.json • retrain via artisan</p>
                <x-filament::button size="sm" color="primary" wire:click="trainAll" wire:confirm="Latih ulang semua model?">Train Semua</x-filament::button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5">Fasilitas</th>
                            <th class="px-4 py-2.5">Obat</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">R²</th>
                            <th class="px-4 py-2.5 text-right">MAPE</th>
                            <th class="px-4 py-2.5 text-right">Tgl</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($models as $m)
                            @php $color = \App\Models\ModelPrediksi::getStatusColor($m->status); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $m->fasilitas->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $m->obat->nama_obat ?? '-' }}</td>
                                <td class="px-4 py-3"><x-filament::badge :color="$color">{{ $m->status }}</x-filament::badge></td>
                                <td class="px-4 py-3 text-right">{{ $m->akurasi_r2 !== null ? number_format((float) $m->akurasi_r2 * 100, 1).'%' : '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $m->mape !== null ? number_format((float) $m->mape, 1).'%' : '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $m->tanggal_training?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-filament::button size="xs" color="gray" wire:click="trainModel({{ $m->id }})" wire:confirm="Latih ulang model ini?">Train Ulang</x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada model AI — jalankan <code>php artisan ai:train-models</code></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </div>
    </div>
</x-filament-panels::page>
