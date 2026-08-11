@php
    use App\Filament\Resources\PenerimaanStoks\Pages\CreatePenerimaanStok;
    use App\Models\PenerimaanStok;
    use Filament\Support\Enums\IconSize;
    use Stokobat\Boxicons\Boxicon;

    $hasFilter = filled($this->search)
        || filled($this->activeStatus)
        || filled($this->filterTanggal['from'] ?? null)
        || filled($this->filterTanggal['to'] ?? null)
        || filled($this->filterSupplier)
        || filled($this->filterSumberDana)
        || filled($this->filterFaskesId);

    $canCreate = auth()->user()?->can('create', PenerimaanStok::class) ?? false;
@endphp

<section class="fi-empty-state">
    <div class="fi-empty-state-content">
        <div class="fi-empty-state-icon-bg fi-color fi-color-primary">
            {{ \Filament\Support\generate_icon_html(
                $hasFilter ? Boxicon::FileSearch : Boxicon::Package,
                size: IconSize::Large,
            ) }}
        </div>

        <div class="fi-empty-state-text-ctn">
            <h2 class="fi-empty-state-heading">
                @if ($hasFilter)
                    Tidak ada penerimaan stok yang cocok
                @else
                    Belum ada penerimaan stok
                @endif
            </h2>

            <p class="fi-empty-state-description">
                @if ($hasFilter)
                    Tidak ada penerimaan stok yang sesuai dengan pencarian atau filter yang dipilih.
                @else
                    Anda belum mencatat penerimaan stok. Buat penerimaan pertama untuk mulai mendata stok obat yang masuk.
                @endif
            </p>

            <footer class="fi-empty-state-footer">
                @if ($hasFilter)
                    <button
                        type="button"
                        wire:click="resetFilters()"
                        class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 font-semibold rounded-lg bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10 fi-btn-label"
                    >
                        Reset Filter
                    </button>
                @elseif ($canCreate)
                    <a
                        href="{{ CreatePenerimaanStok::getUrl() }}"
                        class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 font-semibold rounded-lg bg-primary-600 text-white shadow-sm hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 fi-btn-label dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        <span class="fi-btn-icon">
                            {{ \Filament\Support\generate_icon_html(Boxicon::PlusCircle, size: IconSize::Small) }}
                        </span>
                        Buat Penerimaan
                    </a>
                @endif
            </footer>
        </div>
    </div>
</section>
