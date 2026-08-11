<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Step Indicator --}}
        <div class="flex items-center justify-center gap-2 mb-8">
            @foreach ([1 => 'Upload & Konfigurasi', 2 => 'Preview', 3 => 'Hasil'] as $step => $label)
                <div class="flex items-center">
                    <div @class([
                        'flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium',
                        'bg-primary-600 text-white' => $currentStep === $step,
                        'bg-success-600 text-white' => $currentStep > $step,
                        'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $currentStep < $step,
                    ])>
                        @if ($currentStep > $step)
                            <x-heroicon-m-check class="w-4 h-4" />
                        @else
                            {{ $step }}
                        @endif
                    </div>
                    <span @class([
                        'ml-2 text-sm font-medium',
                        'text-primary-600 dark:text-primary-400' => $currentStep === $step,
                        'text-gray-500 dark:text-gray-400' => $currentStep !== $step,
                    ])>
                        {{ $label }}
                    </span>
                    @if ($step < 3)
                        <div class="w-8 h-0.5 mx-3 bg-gray-300 dark:bg-gray-600"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step 1: Upload & Config --}}
        @if ($currentStep === 1)
            <div class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button
                        wire:click="runPreview"
                        icon="heroicon-m-eye"
                        size="lg"
                    >
                        Preview Data
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Step 2: Preview --}}
        @if ($currentStep === 2)
            <div class="space-y-6">
                @if (empty($previewData))
                    <x-filament::section>
                        <div class="text-center py-8 text-gray-500">
                            <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-2 text-warning-500" />
                            <p>Tidak ada data untuk ditampilkan. Kembali ke langkah 1.</p>
                        </div>
                    </x-filament::section>
                @else
                    @foreach ($previewData as $faskesName => $data)
                        @php
                            $validation = $validationResults[$faskesName] ?? [];
                            $hasErrors = !empty($validation['errors'] ?? []);
                            $hasWarnings = !empty($validation['warnings'] ?? []);
                        @endphp

                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    {{ $faskesName }}
                                    @if ($hasErrors)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">
                                            Error
                                        </span>
                                    @elseif ($hasWarnings)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">
                                            Warning
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                                            Ready
                                        </span>
                                    @endif
                                </div>
                            </x-slot>
                            <x-slot name="description">
                                Format: {{ $data['format'] }} | {{ $data['total_rows'] }} obat |
                                Faskes: {{ ($data['faskes_exists'] ?? false) ? 'Sudah ada (ID: ' . ($data['faskes_id'] ?? '-') . ')' : 'Baru (auto-create)' }}
                            </x-slot>

                            @if (!empty($validation['warnings']))
                                <div class="mb-4 p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800">
                                    @foreach ($validation['warnings'] as $warning)
                                        <p class="text-sm text-warning-700 dark:text-warning-300">⚠ {{ $warning }}</p>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($validation['errors']))
                                <div class="mb-4 p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                                    @foreach ($validation['errors'] as $error)
                                        <p class="text-sm text-danger-700 dark:text-danger-300">✗ {{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-3 py-2">Kode</th>
                                            <th class="px-3 py-2">Nama Obat</th>
                                            <th class="px-3 py-2 text-right">Harga</th>
                                            <th class="px-3 py-2 text-right">Total Penerimaan</th>
                                            <th class="px-3 py-2 text-right">Total Pemakaian</th>
                                            <th class="px-3 py-2 text-right">Stok Akhir (Des)</th>
                                            <th class="px-3 py-2 text-right">RKO</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach (array_slice($data['obat'], 0, 5) as $obat)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="px-3 py-2 font-mono text-xs">{{ $obat['kode_obat'] }}</td>
                                                <td class="px-3 py-2 max-w-[200px] truncate">{{ $obat['nama_obat'] }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($obat['harga'], 0, ',', '.') }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($obat['total_penerimaan'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($obat['total_pemakaian'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2 text-right font-medium">{{ number_format($obat['stok_akhir_des'] ?? 0, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2 text-right">{{ number_format($obat['rko'] ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if (count($data['obat']) > 5)
                                    <p class="mt-2 text-xs text-gray-500 text-center">... dan {{ count($data['obat']) - 5 }} obat lainnya</p>
                                @endif
                            </div>
                        </x-filament::section>
                    @endforeach
                @endif

                <div class="flex justify-between">
                    <x-filament::button
                        wire:click="goToStep(1)"
                        icon="heroicon-m-arrow-left"
                        color="gray"
                    >
                        Kembali
                    </x-filament::button>
                    <x-filament::button
                        wire:click="runImport"
                        icon="heroicon-m-arrow-up-tray"
                        size="lg"
                        :color="$dryRun ? 'warning' : 'primary'"
                    >
                        {{ $dryRun ? 'Jalankan Dry-Run' : 'Import Sekarang' }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Step 3: Results --}}
        @if ($currentStep === 3)
            <div class="space-y-6">
                @if (empty($importResults))
                    <x-filament::section>
                        <div class="text-center py-8 text-gray-500">
                            <p>Tidak ada hasil import.</p>
                        </div>
                    </x-filament::section>
                @else
                    @foreach ($importResults as $faskesName => $result)
                        @php
                            $hasErrors = !empty($result['errors']);
                        @endphp

                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    {{ $faskesName }}
                                    @if ($result['dry_run'] ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400">
                                            DRY-RUN
                                        </span>
                                    @endif
                                    @if ($hasErrors)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">
                                            Error
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                                            Success
                                        </span>
                                    @endif
                                </div>
                            </x-slot>

                            @if (!empty($result['errors']))
                                <div class="mb-4 p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                                    @foreach ($result['errors'] as $error)
                                        <p class="text-sm text-danger-700 dark:text-danger-300">✗ {{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($result['targets']))
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($result['targets'] as $target => $targetResult)
                                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                {{ match($target) {
                                                    'stok_faskes' => 'Stok Faskes',
                                                    'lplpo' => 'LPLPO',
                                                    'rko' => 'RKO',
                                                    'penerimaan' => 'Penerimaan Stok',
                                                    'pemakaian' => 'Pemakaian Obat',
                                                    default => $target,
                                                } }}
                                            </h4>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-0.5">
                                                @if (isset($targetResult['count']))
                                                    <p>{{ $targetResult['count'] }} record</p>
                                                @endif
                                                @if (isset($targetResult['reports']))
                                                    <p>{{ $targetResult['reports'] }} laporan</p>
                                                @endif
                                                @if (isset($targetResult['details']))
                                                    <p>{{ $targetResult['details'] }} detail rows</p>
                                                @endif
                                                @if (isset($targetResult['total_anggaran']))
                                                    <p>Anggaran: Rp {{ number_format($targetResult['total_anggaran'], 0, ',', '.') }}</p>
                                                @endif
                                                @if (isset($targetResult['skipped']))
                                                    <p class="text-warning-600">Skipped: {{ $targetResult['reason'] ?? '' }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </x-filament::section>
                    @endforeach
                @endif

                <div class="flex justify-between">
                    <x-filament::button
                        wire:click="goToStep(2)"
                        icon="heroicon-m-arrow-left"
                        color="gray"
                    >
                        Kembali ke Preview
                    </x-filament::button>
                    <x-filament::button
                        wire:click="resetWizard"
                        icon="heroicon-m-arrow-path"
                    >
                        Import Lagi
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
