<?php

namespace App\Filament\Resources\PenerimaanStoks\Pages;

use App\Filament\Forms\Components\DateRangeFilter;
use App\Filament\Forms\Components\SearchInput;
use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Filament\Resources\PenerimaanStoks\Tables\PenerimaanStoksTable;
use App\Models\FasilitasKesehatan;
use App\Models\PenerimaanStok;
use App\Models\SumberDana;
use App\Models\Supplier;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class ListPenerimaanStoks extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PenerimaanStokResource::class;

    protected string $view = 'filament.pages.penerimaan-stok.list-penerimaan-stok';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    public ?int $filterSupplier = null;

    public ?int $filterSumberDana = null;

    public ?string $activeTab = 'dinas';

    public ?int $filterFaskesId = null;

    public bool $isDinasUser = false;

    protected function getForms(): array
    {
        return ['form', 'filtersForm'];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('activeStatus')
                            ->label('Status')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Status')
                            ->options(fn ($component): array => [
                                'draft' => 'Draft ('.$component->getLivewire()->statusCounts()['draft'].')',
                                'dikonfirmasi' => 'Dikonfirmasi ('.$component->getLivewire()->statusCounts()['dikonfirmasi'].')',
                                'dibatalkan' => 'Dibatalkan ('.$component->getLivewire()->statusCounts()['dibatalkan'].')',
                            ]),
                        Select::make('filterFaskesId')
                            ->label('Fasilitas')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Fasilitas')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => $component->getLivewire()->isDinasUser && $component->getLivewire()->activeTab === 'faskes'),
                        Select::make('filterSupplier')
                            ->label('Supplier')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Supplier')
                            ->options(fn (): Collection => Supplier::whereHas('penerimaanStok')->orderBy('nama')->pluck('nama', 'id')),
                        Select::make('filterSumberDana')
                            ->label('Sumber Dana')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Sumber Dana')
                            ->options(fn (): Collection => SumberDana::orderBy('kode')->pluck('kode', 'id'))
                            ->visible(fn ($component): bool => $component->getLivewire()->isDinasUser && $component->getLivewire()->activeTab === 'dinas'),
                        DateRangeFilter::make('filterTanggal')
                            ->label('Tanggal Penerimaan')
                            ->btnLabel('Filter Tanggal')
                            ->live(),
                        SearchInput::make('search')
                            ->placeholder('Cari nomor, catatan, PO, invoice...')
                            ->hiddenLabel()
                            ->live()
                            ->debounce(300)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function statusCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $filteredQuery = $this->isDinasUser
            ? ($this->activeTab === 'dinas'
                ? (clone $query)->whereNull('fasilitas_id')
                : (clone $query)->whereNotNull('fasilitas_id'))
            : (clone $query);

        return [
            'draft' => (clone $filteredQuery)->where('status', 'draft')->count(),
            'dikonfirmasi' => (clone $filteredQuery)->where('status', 'dikonfirmasi')->count(),
            'dibatalkan' => (clone $filteredQuery)->where('status', 'dibatalkan')->count(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        $this->isDinasUser = $user->hasAnyRole(['super_admin', 'admin_dinas', 'admin_gudang']);

        if ($this->isDinasUser) {
            $this->activeTab = 'dinas';
        }

        if (! $this->isDinasUser) {
            return;
        }

        if (SumberDana::where('status', 'aktif')->exists()) {
            return;
        }

        session()->flash('sumber_dana_alert', true);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Penerimaan')
                ->icon(Boxicon::PlusCircle)
                ->visible(fn (): bool => auth()->user()?->can('create', PenerimaanStok::class) ?? false),
        ];
    }

    public function table(Table $table): Table
    {
        return PenerimaanStoksTable::configure($table, $this->activeTab);
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_penerimaan', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%")
                ->orWhere('nomor_po', 'like', "%{$v}%")
                ->orWhere('nomor_invoice', 'like', "%{$v}%");
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_penerimaan', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_penerimaan', '<=', $v));
        $query->when($this->filterSupplier, fn (Builder $q, int $v) => $q->where('supplier_id', $v));
        $query->when($this->filterSumberDana, fn (Builder $q, int $v) => $q->where('sumber_dana_id', $v));

        if ($this->isDinasUser) {
            match ($this->activeTab) {
                'dinas' => $query->whereNull('fasilitas_id'),
                'faskes' => $query->whereNotNull('fasilitas_id')
                    ->when($this->filterFaskesId, fn (Builder $q, int $v) => $q->where('fasilitas_id', $v)),
            };
        }

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetTable();
    }

    public function updatedFilterSupplier(): void
    {
        $this->resetTable();
    }

    public function updatedFilterSumberDana(): void
    {
        $this->resetTable();
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'dinas') {
            $this->filterFaskesId = null;
        }
        $this->resetTable();
    }

    public function updatedFilterFaskesId(): void
    {
        $this->resetTable();
    }

    public function filterByTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'dinas') {
            $this->filterFaskesId = null;
        }
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTanggal',
            'filterSupplier',
            'filterSumberDana',
            'filterFaskesId',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'penerimaanDinasCount' => (clone $query)->whereNull('fasilitas_id')->count(),
            'penerimaanFaskesCount' => (clone $query)->whereNotNull('fasilitas_id')->count(),
        ];
    }
}
