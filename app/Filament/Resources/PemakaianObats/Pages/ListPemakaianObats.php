<?php

namespace App\Filament\Resources\PemakaianObats\Pages;

use App\Filament\Forms\Components\DateRangeFilter;
use App\Filament\Forms\Components\SearchInput;
use App\Filament\Resources\PemakaianObats\PemakaianObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\PemakaianObat;
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

class ListPemakaianObats extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PemakaianObatResource::class;

    protected string $view = 'filament.pages.pemakaian-obat.list-pemakaian-obat';

    public ?string $search = null;

    public ?string $filterJenis = null;

    public ?int $filterFaskesId = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    public bool $isDinasUser = false;

    protected function getForms(): array
    {
        return ['form', 'filtersForm'];
    }

    public function mount(): void
    {
        parent::mount();

        $this->isDinasUser = Auth::user()->hasAnyRole(['super_admin', 'admin_dinas', 'admin_gudang']);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('filterFaskesId')
                            ->label('Fasilitas')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Fasilitas')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => $component->getLivewire()->isDinasUser),
                        Select::make('filterJenis')
                            ->label('Pelayanan')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Pelayanan')
                            ->options(fn ($component): array => [
                                'rawat_jalan' => 'Rawat Jalan ('.$component->getLivewire()->jenisCounts()['rawat_jalan'].')',
                                'rawat_inap' => 'Rawat Inap ('.$component->getLivewire()->jenisCounts()['rawat_inap'].')',
                                'uks' => 'UKS ('.$component->getLivewire()->jenisCounts()['uks'].')',
                                'posyandu' => 'Posyandu ('.$component->getLivewire()->jenisCounts()['posyandu'].')',
                                'pusling' => 'Pusling ('.$component->getLivewire()->jenisCounts()['pusling'].')',
                                'gigi' => 'Poli Gigi ('.$component->getLivewire()->jenisCounts()['gigi'].')',
                                'laboratorium' => 'Laboratorium ('.$component->getLivewire()->jenisCounts()['laboratorium'].')',
                                'apotek' => 'Apotek ('.$component->getLivewire()->jenisCounts()['apotek'].')',
                                'lainnya' => 'Lainnya ('.$component->getLivewire()->jenisCounts()['lainnya'].')',
                            ]),
                        DateRangeFilter::make('filterTanggal')
                            ->label('Tanggal')
                            ->btnLabel('Rentang Tanggal Pakai')
                            ->live(),
                        SearchInput::make('search')
                            ->placeholder('Cari nomor pemakaian, nama pasien...')
                            ->hiddenLabel()
                            ->live()
                            ->debounce(300)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function jenisCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'rawat_jalan' => (clone $query)->where('jenis_pelayanan', 'rawat_jalan')->count(),
            'rawat_inap' => (clone $query)->where('jenis_pelayanan', 'rawat_inap')->count(),
            'uks' => (clone $query)->where('jenis_pelayanan', 'uks')->count(),
            'posyandu' => (clone $query)->where('jenis_pelayanan', 'posyandu')->count(),
            'pusling' => (clone $query)->where('jenis_pelayanan', 'pusling')->count(),
            'gigi' => (clone $query)->where('jenis_pelayanan', 'gigi')->count(),
            'laboratorium' => (clone $query)->where('jenis_pelayanan', 'laboratorium')->count(),
            'apotek' => (clone $query)->where('jenis_pelayanan', 'apotek')->count(),
            'lainnya' => (clone $query)->where('jenis_pelayanan', 'lainnya')->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Pemakaian')
                ->icon(Boxicon::PlusCircle)
                ->modalHeading('Buat Pemakaian Obat Baru')
                ->modalIcon(Boxicon::PlusCircle)
                ->visible(fn (): bool => auth()->user()?->can('create', PemakaianObat::class) ?? false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_pemakaian', 'like', "%{$v}%")
                ->orWhere('nama_pasien', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%");
        }));

        $query->when($this->filterJenis, fn (Builder $q, string $v) => $q->where('jenis_pelayanan', $v));
        $query->when($this->filterFaskesId, fn (Builder $q, int $v) => $q->where('fasilitas_id', $v));
        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_pemakaian', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_pemakaian', '<=', $v));

        return $query;
    }

    public function updatedFilterJenis(): void
    {
        $this->resetTable();
    }

    public function updatedFilterFaskesId(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterJenis',
            'filterFaskesId',
            'filterTanggal',
        ]);
        $this->resetTable();
    }
}
