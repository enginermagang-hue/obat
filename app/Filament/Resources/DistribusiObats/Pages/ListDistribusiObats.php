<?php

namespace App\Filament\Resources\DistribusiObats\Pages;

use App\Filament\Forms\Components\DateRangeFilter;
use App\Filament\Forms\Components\SearchInput;
use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class ListDistribusiObats extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DistribusiObatResource::class;

    protected string $view = 'filament.pages.distribusi-obat.list-distribusi-obat';

    public ?string $search = null;

    public ?string $activeTab = 'dinas';

    public ?string $activeStatus = null;

    public ?string $filterTipe = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    public ?string $filterPengirim = null;

    public ?string $filterPenerima = null;

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
                            ->placeholder('Semua')
                            ->options(fn ($component): array => [
                                'draft' => 'Draft ('.$component->getLivewire()->statusCounts()['draft'].')',
                                'dalam_pengiriman' => 'Dalam Pengiriman ('.$component->getLivewire()->statusCounts()['dalam_pengiriman'].')',
                                'diterima' => 'Diterima ('.$component->getLivewire()->statusCounts()['diterima'].')',
                                'ditolak' => 'Ditolak ('.$component->getLivewire()->statusCounts()['ditolak'].')',
                            ]),
                        Select::make('filterTipe')
                            ->label('Tipe')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Tipe')
                            ->options([
                                'puskesmas_ke_pustu' => 'Puskesmas \u2192 Pustu',
                                'dinas_ke_puskesmas' => 'Dinas \u2192 Puskesmas',
                            ])
                            ->visible(fn ($component): bool => ! $component->getLivewire()->isDinasUser),
                        DateRangeFilter::make('filterTanggal')
                            ->label('Tanggal')
                            ->btnLabel('Rentang Tanggal Kirim')
                            ->live(),
                        Select::make('filterPengirim')
                            ->label('Pengirim')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Pengirim')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => ! ($component->getLivewire()->isDinasUser && $component->getLivewire()->activeTab === 'puskesmas')),
                        Select::make('filterPenerima')
                            ->label('Penerima')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Penerima')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => ! ($component->getLivewire()->isDinasUser && $component->getLivewire()->activeTab === 'puskesmas')),
                        SearchInput::make('search')
                            ->placeholder('Cari nomor surat jalan, catatan...')
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
                ? (clone $query)->where('tipe_distribusi', 'dinas_ke_puskesmas')
                : (clone $query)->where('tipe_distribusi', 'puskesmas_ke_pustu'))
            : (clone $query);

        return [
            'draft' => (clone $filteredQuery)->where('status', 'draft')->count(),
            'dalam_pengiriman' => (clone $filteredQuery)->where('status', 'dalam_pengiriman')->count(),
            'diterima' => (clone $filteredQuery)->where('status', 'diterima')->count(),
            'ditolak' => (clone $filteredQuery)->where('status', 'ditolak')->count(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $this->isDinasUser = Auth::user()->hasAnyRole(['super_admin', 'admin_dinas', 'admin_gudang']);

        if ($this->isDinasUser) {
            $this->activeTab = 'dinas';
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Distribusi')
                ->icon(Boxicon::PlusCircle)
                ->visible(fn (): bool => auth()->user()?->can('create', DistribusiObat::class) ?? false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_surat_jalan', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%");
        }));

        if ($this->isDinasUser) {
            match ($this->activeTab) {
                'dinas' => $query->where('tipe_distribusi', 'dinas_ke_puskesmas'),
                'puskesmas' => $query->where('tipe_distribusi', 'puskesmas_ke_pustu'),
            };
        } else {
            $query->when($this->filterTipe, fn (Builder $q, string $v) => $q->where('tipe_distribusi', $v));
        }

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_kirim', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_kirim', '<=', $v));
        $query->when($this->filterPengirim, fn (Builder $q, string $v) => $q->where('fasilitas_pengirim_id', $v));
        $query->when($this->filterPenerima, fn (Builder $q, string $v) => $q->where('fasilitas_penerima_id', $v));

        return $query;
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTipe(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetTable();
    }

    public function updatedFilterPengirim(): void
    {
        $this->resetTable();
    }

    public function updatedFilterPenerima(): void
    {
        $this->resetTable();
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }

    public function filterByTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->filterPengirim = null;
        $this->filterPenerima = null;
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTipe',
            'filterTanggal',
            'filterPengirim',
            'filterPenerima',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'tabCounts' => [
                'dinas' => (clone $query)->where('tipe_distribusi', 'dinas_ke_puskesmas')->count(),
                'puskesmas' => (clone $query)->where('tipe_distribusi', 'puskesmas_ke_pustu')->count(),
            ],
        ];
    }
}
