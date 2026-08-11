<x-filament-panels::page>
    <x-filament::section>
        <details open class="mb-6 rounded-xl bg-gray-50 dark:bg-white/5 text-sm text-gray-600 dark:text-gray-400">
            <summary class="cursor-pointer select-none px-4 py-3 font-semibold hover:bg-gray-100 dark:hover:bg-white/10 rounded-xl transition-colors">
                Placeholder yang tersedia
            </summary>
            <div class="px-4 pb-4">
                <table class="w-full text-xs leading-relaxed">
                    <tbody>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{PREFIX}</td>
                            <td class="py-1.5 align-top">Kode prefix otomatis berdasarkan tipe dokumen.<br>Contoh: <span class="font-mono">RQ</span> (Permintaan), <span class="font-mono">PO</span> (Penerimaan), <span class="font-mono">RET</span> (Retur), <span class="font-mono">PMK</span> (Pemakaian), <span class="font-mono">OPN</span>/<span class="font-mono">STK-AWAL</span>/<span class="font-mono">STK-BARU</span> (Opname), <span class="font-mono">SJ</span> (Surat Jalan)</td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{FASKES}</td>
                            <td class="py-1.5 align-top">Kode fasilitas kesehatan dari data master faskes.</td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{YYYY}</td>
                            <td class="py-1.5 align-top">Tahun 4 digit. Contoh: <span class="font-mono">2026</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{YY}</td>
                            <td class="py-1.5 align-top">Tahun 2 digit. Contoh: <span class="font-mono">26</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{MM}</td>
                            <td class="py-1.5 align-top">Bulan 2 digit (leading zero). Contoh: <span class="font-mono">06</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{M}</td>
                            <td class="py-1.5 align-top">Bulan tanpa leading zero. Contoh: <span class="font-mono">6</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{DD}</td>
                            <td class="py-1.5 align-top">Hari 2 digit (leading zero). Contoh: <span class="font-mono">12</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{D}</td>
                            <td class="py-1.5 align-top">Hari tanpa leading zero. Contoh: <span class="font-mono">12</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{YYYYMM}</td>
                            <td class="py-1.5 align-top">Tahun dan bulan. Contoh: <span class="font-mono">202606</span></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{YYYYMMDD}</td>
                            <td class="py-1.5 align-top">Tahun, bulan, dan hari. Contoh: <span class="font-mono">20260612</span></td>
                        </tr>
                        <tr>
                            <td class="font-mono py-1.5 pr-4 align-top whitespace-nowrap">{Urut:N}</td>
                            <td class="py-1.5 align-top">Nomor urut otomatis dengan padding <span class="font-mono">N</span> digit, dihitung per fasilitas kesehatan. Contoh: <span class="font-mono">{Urut:4}</span> → <span class="font-mono">0001</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    
        <form wire:submit="save">
            {{ $this->form }}
    
            <div class="flex gap-4 justify-start mt-6">
                <x-filament::button type="submit">
                    Simpan Pengaturan
                </x-filament::button>
                <x-filament::button
                    wire:click="resetToDefaults"
                    wire:confirm="Apakah Anda yakin ingin mengembalikan semua format nomor ke pengaturan default?"
                    color="danger"
                    icon="heroicon-o-arrow-path"
                >
                    Reset ke Default
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
