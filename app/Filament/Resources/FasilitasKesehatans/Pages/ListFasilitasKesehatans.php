<?php

namespace App\Filament\Resources\FasilitasKesehatans\Pages;

use App\Filament\Resources\FasilitasKesehatans\FasilitasKesehatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListFasilitasKesehatans extends ListRecords
{
    protected static string $resource = FasilitasKesehatanResource::class;

    protected static ?string $title = 'Daftar Fasilitas Kesehatan';

    protected string $view = 'filament.pages.faskes.list-faskes';

    public ?string $search = null;

    public ?string $filterTipe = null;

    public ?string $filterStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Faskes')
                ->icon(Boxicon::PlusCircle)
                ->modalWidth(Width::Medium)
                ->modalHeading('Tambah Fasilitas')
                ->modalIcon(Boxicon::PlusCircle)
                ->modalFooterActionsAlignment('end')
                ->modalSubmitActionLabel('Tambah Faskes')
                ->createAnother(false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('kode_faskes', 'like', "%{$v}%")
                ->orWhere('nama', 'like', "%{$v}%")
                ->orWhere('pic', 'like', "%{$v}%")
                ->orWhere('kontak_pic', 'like', "%{$v}%")
                ->orWhere('alamat', 'like', "%{$v}%");
        }));

        $query->when($this->filterTipe, fn (Builder $q, string $v) => $q->where('tipe', $v));
        $query->when($this->filterStatus, fn (Builder $q, string $v) => $q->where('status', $v));

        return $query;
    }

    public function updatedFilterStatus(): void
    {
        $this->resetTable();
    }

    public function filterByTipe(?string $tipe): void
    {
        $this->filterTipe = $tipe;
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterTipe',
            'filterStatus',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'tipeCounts' => [
                'puskesmas' => (clone $query)->where('tipe', 'puskesmas')->count(),
                'pustu' => (clone $query)->where('tipe', 'pustu')->count(),
            ],
            'statusCounts' => [
                'aktif' => (clone $query)->where('status', 'aktif')->count(),
                'nonaktif' => (clone $query)->where('status', 'nonaktif')->count(),
            ],
        ];
    }
}
