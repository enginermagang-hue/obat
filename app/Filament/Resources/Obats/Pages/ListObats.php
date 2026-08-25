<?php

namespace App\Filament\Resources\Obats\Pages;

use App\Filament\Resources\Obats\Importers\ObatImporter;
use App\Filament\Resources\Obats\ObatResource;
use App\Models\Obat;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListObats extends ListRecords
{
    protected static string $resource = ObatResource::class;

    protected string $view = 'filament.pages.obat.list-obat';

    public ?string $search = null;

    public ?string $filterVen = null;

    public ?string $filterStatus = null;

    public ?string $filterKategori = null;

    public ?string $filterBentuk = null;

    public ?string $filterMetode = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ObatImporter::class)
                ->icon(Boxicon::ArrowInUpSquareHalf),
            CreateAction::make()
                ->label('Tambah Obat')
                ->icon(Boxicon::PlusCircle)
                ->modalHeading('Tambah Obat Baru')
                ->modalIcon(Boxicon::PlusCircle)
                ->modalWidth(Width::ExtraLarge)
                ->modalSubmitActionLabel('Tambah')
                ->createAnother(false)
                ->modalFooterActionsAlignment(Alignment::End),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('kode_obat', 'like', "%{$v}%")
                ->orWhere('nama_obat', 'like', "%{$v}%")
                ->orWhere('nama_generik', 'like', "%{$v}%")
                ->orWhere('kategori', 'like', "%{$v}%");
        }));

        $query->when($this->filterVen, fn (Builder $q, string $v) => $q->where('ven_kategori', $v));
        $query->when($this->filterStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterKategori, fn (Builder $q, string $v) => $q->where('kategori', $v));
        $query->when($this->filterBentuk, fn (Builder $q, string $v) => $q->where('bentuk_sediaan', $v));
        $query->when($this->filterMetode, fn (Builder $q, string $v) => $q->where('metode_stok', $v));
        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('created_at', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('created_at', '<=', $v));

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }

    public function updatedFilterVen(): void
    {
        $this->resetTable();
    }

    public function filterByVen(?string $value): void
    {
        $this->filterVen = $value;
        $this->resetTable();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterKategori(): void
    {
        $this->resetTable();
    }

    public function updatedFilterBentuk(): void
    {
        $this->resetTable();
    }

    public function updatedFilterMetode(): void
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
            'filterVen',
            'filterStatus',
            'filterKategori',
            'filterBentuk',
            'filterMetode',
            'filterTanggal',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'venCounts' => [
                'V' => (clone $query)->where('ven_kategori', 'V')->count(),
                'E' => (clone $query)->where('ven_kategori', 'E')->count(),
                'N' => (clone $query)->where('ven_kategori', 'N')->count(),
            ],
            'kategoriOptions' => Obat::distinct()->pluck('kategori', 'kategori')->toArray(),
            'bentukOptions' => [
                'tablet' => 'Tablet',
                'kapsul' => 'Kapsul',
                'sirup' => 'Sirup',
                'salep' => 'Salep',
                'injeksi' => 'Injeksi',
                'drop' => 'Drop',
                'inhaler' => 'Inhaler',
                'suppositoria' => 'Suppositoria',
            ],
            'metodeOptions' => [
                'fefo' => 'FEFO',
                'fifo' => 'FIFO',
                'lifo' => 'LIFO',
            ],
        ];
    }
}
