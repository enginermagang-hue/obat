<x-filament-panels::page>
    @php
        $kpi = $this->getKpi();
        $ringkasan = $this->getRingkasan();
        $abven = $this->getAbven();
        $musim = $this->getTrenMusim();
        $risiko = $this->getRisiko();
        $rekomendasi = $this->getRekomendasi();
        $fasilitas = $this->getScopeNama();
        $tahun = (int) $this->tahun;

        $fmtNum = fn ($v) => number_format($v, 0, ',', '.');
        $fmtRp = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
        $fmtShort = fn ($v) => $v >= 1_000_000 ? number_format($v / 1_000_000, 1, ',', '.') . ' Jt' : ($v >= 1000 ? number_format($v / 1000, 1, ',', '.') . ' Rb' : (string) $v);
    @endphp

    <div class="space-y-4">
        {{-- ═══════════ Header + Filter ═══════════ --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Analisis Lengkap Kebutuhan Obat</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Deep-dive analytics: konsumsi, ABC-VEN, tren, risiko stockout, dan rekomendasi — Tahun {{ $tahun }} • {{ $fasilitas }}</p>
            </div>

            <div class="mt-3">{{ $this->form }}</div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="#abven" class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">ABC-VEN</a>
                <a href="#tren" class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">Tren</a>
                <a href="#risiko" class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">Risiko Stockout</a>
                <a href="#rekomendasi" class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">Rekomendasi</a>
            </div>
        </div>

        {{-- ═══════════ KPI ═══════════ --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Konsumsi {{ $tahun }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $fmtShort($kpi['konsumsi']) }}<span class="text-sm font-normal text-gray-400"> unit</span></div>
                <div class="mt-1 text-xs {{ $kpi['konsumsi_yoy'] === null ? 'text-gray-400' : ($kpi['konsumsi_yoy'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $kpi['konsumsi_yoy'] === null ? '—' : (($kpi['konsumsi_yoy'] >= 0 ? '+' : '') . number_format($kpi['konsumsi_yoy'], 1, ',', '.') . '% YoY') }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Service Level</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $kpi['service_level'] === null ? '—' : number_format($kpi['service_level'], 1, ',', '.') . '%' }}</div>
                <div class="mt-1 text-xs text-gray-400">obat stok ≥ kebutuhan rata-rata</div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Obat Berisiko Stockout</div>
                <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $kpi['berisiko'] }}<span class="text-sm font-normal text-gray-400"> item</span></div>
                <div class="mt-1 text-xs text-gray-400">coverage &lt; 21 hari</div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Expired / Waste</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $fmtShort($kpi['waste_nilai']) }}</div>
                <div class="mt-1 text-xs text-gray-400">{{ $kpi['waste_pct'] === null ? '—' : number_format($kpi['waste_pct'], 1, ',', '.') . '% dari nilai konsumsi' }}</div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Akurasi Model AI</div>
                <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($kpi['akurasi'] * 100, 1, ',', '.') }}%</div>
                <div class="mt-1 text-xs text-gray-400">R² rata-rata model aktif</div>
            </div>
        </div>

        {{-- ═══════════ Ringkasan AI ═══════════ --}}
        <div class="rounded-xl bg-primary-50 p-4 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/30">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                <span class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Ringkasan AI • {{ $tahun }} • {{ strtoupper($fasilitas) }}</span>
            </div>
            <ol class="mt-2 space-y-2">
                @foreach($ringkasan['temuan'] as $i => $t)
                    <li class="flex gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-bold text-primary-600 dark:text-primary-400">0{{ $i + 1 }}</span>
                        <span>{{ $t }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Confidence AI {{ number_format($ringkasan['confidence'], 1, ',', '.') }}% • Diperbarui hari ini</p>
        </div>

        {{-- ═══════════ ABC-VEN ═══════════ --}}
        <div id="abven" class="scroll-mt-20 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Matriks ABC-VEN</h3>
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">A = 70% nilai konsumsi, B = 20% berikutnya, C = sisanya. V = Vital, E = Essential, N = Non-essential.</p>

            <div class="grid grid-cols-4 gap-2 text-center text-sm">
                <div></div>
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">V - Vital</div>
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">E - Essential</div>
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">N - Non Ess.</div>

                @foreach(['A', 'B', 'C'] as $abc)
                    <div class="flex items-center justify-center text-xs font-bold text-gray-500 dark:text-gray-400">{{ $abc }}</div>
                    @foreach(['V', 'E', 'N'] as $ven)
                        @php $sel = $abc . $ven; $hot = $sel === 'AV'; @endphp
                        <div class="rounded-lg p-3 ring-1 {{ $hot ? 'bg-red-500/10 ring-red-500/30' : 'bg-gray-50 ring-gray-200 dark:bg-white/5 dark:ring-white/10' }}">
                            <div class="text-xl font-bold {{ $hot ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $abven['matrix'][$sel] ?? 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sel }}</div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                <span class="font-semibold">Insight:</span>
                Fokus pengendalian pada obat AV berpotensi menghemat
                <span class="font-bold">{{ $fmtRp($abven['hemat_estimate']) }}</span>
                dari pencegahan expired (estimasi).
            </p>
        </div>

        {{-- ═══════════ Top Kategori A ═══════════ --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 p-4 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Top Obat Kategori A • {{ $abven['topKategori'] ? number_format($abven['topKategori']['share'], 1, ',', '.') . '% didominasi ' . $abven['topKategori']['nama'] : '—' }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total nilai konsumsi {{ $tahun }}: {{ $fmtRp($abven['total_nilai']) }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5">Nama Obat</th>
                            <th class="px-4 py-2.5 text-center">VEN</th>
                            <th class="px-4 py-2.5 text-right">Konsumsi</th>
                            <th class="px-4 py-2.5 text-right">Nilai</th>
                            <th class="px-4 py-2.5 text-right">% Angg.</th>
                            <th class="px-4 py-2.5">Saran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse($abven['topA'] as $a)
                            @php $venClass = match($a['ven']) {
                                'V' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                                'E' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                default => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                            }; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $a['nama_obat'] }}</td>
                                <td class="px-4 py-3 text-center"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $venClass }}">{{ $a['ven'] }}</span></td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $fmtNum($a['konsumsi']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $fmtRp($a['nilai']) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($a['share'], 1, ',', '.') }}%</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $a['saran'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada data konsumsi tahun {{ $tahun }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════ Tren ═══════════ --}}
        <div id="tren" class="scroll-mt-20">
            @livewire(App\Filament\Widgets\AnalisisTrenChart::class)
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Bulan Puncak Konsumsi</div>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $musim['puncak'] ?? '—' }}</div>
                <div class="text-xs text-gray-400">{{ isset($musim['puncak_nilai']) ? $fmtNum($musim['puncak_nilai']) . ' unit' : '' }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Rata-rata Bulanan</div>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">{{ $fmtNum($musim['rata_bulanan'] ?? 0) }}<span class="text-sm font-normal text-gray-400"> unit</span></div>
                <div class="text-xs text-gray-400">12 bulan terakhir realisasi</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tren 3 Bulan</div>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                    @if($musim['tren_pct'] === null)
                        —
                    @else
                        {{ ($musim['tren_pct'] >= 0 ? '+' : '') . number_format($musim['tren_pct'], 1, ',', '.') }}%
                    @endif
                </div>
                <div class="text-xs text-gray-400">3 bln terakhir vs 3 bln sebelumnya</div>
            </div>
        </div>

        {{-- ═══════════ Risiko Stockout ═══════════ --}}
        <div id="risiko" class="scroll-mt-20 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-100 p-4 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Analisis Risiko Stockout</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Probabilitas adalah estimasi dari coverage stok (bukan model statistik). Dampak mengikuti VEN.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5">Obat</th>
                            <th class="px-4 py-2.5 text-center">VEN</th>
                            <th class="px-4 py-2.5 text-right">Stok (Hari)</th>
                            <th class="px-4 py-2.5">Prediksi Habis</th>
                            <th class="px-4 py-2.5 text-center">Prob. (Est.)</th>
                            <th class="px-4 py-2.5 text-center">Dampak</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse($risiko as $r)
                            @php
                                $probClass = $r['prob_label'] === 'Tinggi'
                                    ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300'
                                    : ($r['prob_label'] === 'Sedang'
                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300');
                                $dampakClass = $r['dampak'] === 'Tinggi'
                                    ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300'
                                    : ($r['dampak'] === 'Sedang'
                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300');
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $r['nama_obat'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $r['ven'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $r['stok_hari'] === null ? '—' : number_format($r['stok_hari'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $r['habis'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold {{ $probClass }}">{{ $r['prob'] }}% {{ $r['prob_label'] }}</span></td>
                                <td class="px-4 py-3 text-center"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $dampakClass }}">{{ $r['dampak'] }}</span></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="buatPoObat({{ $r['obat_id'] }})"
                                        class="rounded-md bg-primary-600 px-2 py-1 text-xs font-semibold text-white hover:bg-primary-700">
                                        + PO
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Tidak ada obat berisiko untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════ Rekomendasi ═══════════ --}}
        <div id="rekomendasi" class="scroll-mt-20 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rekomendasi Strategis AI • Actionable</h3>
            @forelse($rekomendasi as $i => $rec)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-gray-400">REKOMENDASI 0{{ $i + 1 }}</span>
                        <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">Conf {{ number_format($rec['confidence'], 1, ',', '.') }}%</span>
                    </div>
                    <div class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $rec['judul'] }}</div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $rec['deskripsi'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $rec['dampak'] }}</p>
                    @if($rec['aksi'] === 'po' && ! empty($rec['obat_ids']))
                        <button type="button" wire:click="buatPoRekomendasi({{ $i }})"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-700">
                            Buat PO Terkait
                        </button>
                    @endif
                </div>
            @empty
                <div class="rounded-xl bg-white p-4 text-sm text-gray-400 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    Belum ada rekomendasi untuk filter ini.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
