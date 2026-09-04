@php
    $service = app(\App\Services\RkoAccessCheckService::class);
    $user = \Illuminate\Support\Facades\Auth::user();
    $needsRko = $user ? $service->userNeedsRko($user) : false;
    $isRkoPage = request()->is('admin/rko*');
    $hasPrediksiAccess = $user?->hasPermissionTo('view_prediksi_kebutuhan');
    $prediksiCount = 0;
    if ($user && $hasPrediksiAccess && filled($user->fasilitas_kesehatan_id)) {
        $periode = $service->getPeriodeTahun($user);
        if (filled($periode)) {
            $prediksiCount = \App\Models\PrediksiKebutuhan::where('fasilitas_id', $user->fasilitas_kesehatan_id)->where('periode_tahun', (int) $periode)->count();
        }
    }
@endphp

@if($needsRko && !$isRkoPage)
<div
    x-data
    x-init="
        $el.style.maxHeight = '0';
        $el.style.opacity = '0';
        setTimeout(() => {
            $el.style.maxHeight = '200px';
            $el.style.opacity = '1';
        }, 200)
    "
    style="max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.5s ease-out, opacity 0.5s ease-out;"
    class="bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-800"
>
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 truncate">
                    @php
                        $periodeTahun = $service->getPeriodeTahun($user);
                        $deadline = $service->getDeadline($user);
                        $message = "RKO {$periodeTahun} belum dibuat. Silakan buat RKO untuk periode ini.";
                        if (filled($deadline)) {
                            $deadlineFormatted = \Carbon\Carbon::parse($deadline)->format('d/m/Y');
                            $message .= " Batas waktu: {$deadlineFormatted}.";
                        }
                    @endphp
                    {{ $message }}
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($hasPrediksiAccess)
                    <a
                        href="{{ \App\Filament\Pages\PrediksiAiPage::getUrl(['fasilitas_id' => $user->fasilitas_kesehatan_id, 'tahun' => $service->getPeriodeTahun($user)]) }}"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-amber-700 shadow-sm ring-1 ring-inset ring-amber-300 hover:bg-amber-50 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-700 transition-colors"
                    >
                        <x-heroicon-o-cpu-chip class="h-4 w-4" />
                        Lihat Prediksi{{ $prediksiCount ? " ($prediksiCount)" : '' }}
                    </a>
                @endif
                <a
                    href="{{ \App\Filament\Resources\LaporanRkos\LaporanRkoResource::getUrl('create') }}"
                    class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 transition-colors"
                >
                    <x-heroicon-m-document-plus class="h-4 w-4" />
                    Buat RKO
                </a>
            </div>
        </div>
    </div>
</div>
@endif
