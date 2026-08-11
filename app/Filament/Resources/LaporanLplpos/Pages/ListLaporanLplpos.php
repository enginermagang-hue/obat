<?php

namespace App\Filament\Resources\LaporanLplpos\Pages;

use App\Filament\Forms\Components\SearchInput;
use App\Filament\Resources\LaporanLplpos\LaporanLplpoResource;
use App\Filament\Resources\LaporanLplpos\Tables\LaporanLplposTable;
use App\Models\LaporanLplpo;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListLaporanLplpos extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LaporanLplpoResource::class;

    protected string $view = 'filament.pages.lplpo.list-lplpo';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTahun = null;

    public ?string $activeTab = 'sendiri';

    public bool $isPuskesmasUser = false;

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
                                'selesai' => 'Selesai ('.$component->getLivewire()->statusCounts()['selesai'].')',
                            ]),
                        Select::make('filterTahun')
                            ->label('Tahun')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Tahun')
                            ->options(fn (): array => array_combine(
                                range(now()->year - 2, now()->year + 1),
                                range(now()->year - 2, now()->year + 1),
                            )),
                        SearchInput::make('search')
                            ->placeholder('Cari nomor laporan, faskes...')
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

        return [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        $this->isPuskesmasUser = $user->hasRole('puskesmas');
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $actions = [];

        if ($user->can('create', LaporanLplpo::class) && $this->activeTab === 'sendiri') {
            $actions[] = CreateAction::make();
        }

        return $actions;
    }

    public function table(Table $table): Table
    {
        return LaporanLplposTable::configure($table);
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_laporan', 'like', "%{$v}%")
                ->orWhereHas('fasilitas', function (Builder $q) use ($v) {
                    $q->where('nama', 'like', "%{$v}%");
                });
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTahun, fn (Builder $q, string $v) => $q->where('periode_tahun', $v));

        if ($this->isPuskesmasUser) {
            $user = Auth::user();
            $userFaskesId = $user->fasilitas_kesehatan_id;

            if (filled($userFaskesId)) {
                match ($this->activeTab) {
                    'sendiri' => $query->where('fasilitas_id', $userFaskesId),
                    'pustu_bawahan' => $query->whereHas('fasilitas', fn (Builder $q) => $q->where('puskesmas_induk_id', $userFaskesId)),
                };
            }
        }

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTahun(): void
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
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTahun',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $lplpoSendiriCount = 0;
        $lplpoPustuBawahanCount = 0;

        if ($this->isPuskesmasUser) {
            $user = Auth::user();
            $userFaskesId = $user->fasilitas_kesehatan_id;

            if (filled($userFaskesId)) {
                $lplpoSendiriCount = (clone $query)->where('fasilitas_id', $userFaskesId)->count();
                $lplpoPustuBawahanCount = (clone $query)->whereHas('fasilitas', fn (Builder $q) => $q->where('puskesmas_induk_id', $userFaskesId))->count();
            }
        }

        return [
            'lplpoSendiriCount' => $lplpoSendiriCount,
            'lplpoPustuBawahanCount' => $lplpoPustuBawahanCount,
        ];
    }
}
