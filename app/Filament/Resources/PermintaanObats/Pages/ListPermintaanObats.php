<?php

namespace App\Filament\Resources\PermintaanObats\Pages;

use App\Filament\Forms\Components\DateRangeFilter;
use App\Filament\Forms\Components\SearchInput;
use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\PermintaanObat;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListPermintaanObats extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PermintaanObatResource::class;

    protected string $view = 'filament.pages.permintaan-obat.list-permintaan-obat';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTipe = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    protected function getForms(): array
    {
        return ['form', 'filtersForm'];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->schema([
                Grid::make(3)
                    ->schema([

                        SearchInput::make('search')
                            ->placeholder('Cari nomor permintaan, catatan...')
                            ->hiddenLabel()
                            ->live()
                            ->debounce(300)
                            ->columnSpanFull(),

                        Select::make('activeStatus')
                            ->hiddenLabel()
                            ->prefix('Status')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua')
                            ->options(fn ($component): array => [
                                'menunggu' => 'Menunggu ('.$component->getLivewire()->statusCounts()['menunggu'].')',
                                'disetujui' => 'Disetujui ('.$component->getLivewire()->statusCounts()['disetujui'].')',
                                'diterima' => 'Diterima ('.$component->getLivewire()->statusCounts()['diterima'].')',
                                'ditolak' => 'Ditolak ('.$component->getLivewire()->statusCounts()['ditolak'].')',
                            ]),
                        Select::make('filterTipe')
                            ->hiddenLabel()
                            ->prefix('Tipe')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Tipe')
                            ->options([
                                'pustu_ke_puskesmas' => 'Pustu ke Puskesmas',
                                'puskesmas_ke_dinas' => 'Puskesmas ke Dinas',
                            ])
                            ->allowHtml(),
                        DateRangeFilter::make('filterTanggal')
                            ->label('Tanggal')
                            ->hiddenLabel()
                            ->btnLabel('Rentang Tanggal')
                            ->live(),
                    ]),
            ]);
    }

    public function statusCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'menunggu' => (clone $query)->whereIn('status', ['draft', 'menunggu_persetujuan'])->count(),
            'disetujui' => (clone $query)->whereIn('status', ['disetujui', 'sedang_didistribusi'])->count(),
            'diterima' => (clone $query)->where('status', 'diterima')->count(),
            'ditolak' => (clone $query)->whereIn('status', ['ditolak', 'dibatalkan'])->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => CetakPdfPage::getUrl(['type' => 'faktur-permintaan']))
                ->openUrlInNewTab(),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->url(fn (): string => route('admin.permintaan.cetak-xls'))
                ->openUrlInNewTab(),
            CreateAction::make()
                ->label('Buat Permintaan')
                ->icon(Boxicon::PlusCircle)
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('create', PermintaanObat::class) ?? false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_permintaan', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%");
        }));

        $query->when($this->filterTipe, fn (Builder $q, string $v) => $q->where('tipe_permintaan', $v));

        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_permintaan', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_permintaan', '<=', $v));

        match ($this->activeStatus) {
            'menunggu' => $query->whereIn('status', ['draft', 'menunggu_persetujuan']),
            'disetujui' => $query->whereIn('status', ['disetujui', 'sedang_didistribusi']),
            'diterima' => $query->where('status', 'diterima'),
            'ditolak' => $query->whereIn('status', ['ditolak', 'dibatalkan']),
            default => null,
        };

        return $query;
    }

    public function updatedFilterTipe(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetTable();
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTipe',
            'filterTanggal',
        ]);
        $this->resetTable();
    }
}
