<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected string $view = 'filament.pages.supplier.list-supplier';

    public ?string $search = null;

    public ?string $filterStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Supplier')
                ->icon(Boxicon::PlusCircle)
                ->createAnother(false)
                ->modalHeading('Tambah Supplier')
                ->modalIcon(Boxicon::PlusCircle)
                ->modalWidth('md')
                ->modalSubmitActionLabel('Tambah')
                ->modalFooterActionsAlignment('end'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nama', 'like', "%{$v}%")
                ->orWhere('telepon', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")
                ->orWhere('alamat', 'like', "%{$v}%")
                ->orWhere('npwp', 'like', "%{$v}%");
        }));

        $query->when($this->filterStatus, fn (Builder $q, string $v) => $q->where('status', $v));

        return $query;
    }

    public function updatedFilterStatus(): void
    {
        $this->resetTable();
    }

    public function filterByStatus(?string $status): void
    {
        $this->filterStatus = $status;
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterStatus',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'statusCounts' => [
                'aktif' => (clone $query)->where('status', 'aktif')->count(),
                'nonaktif' => (clone $query)->where('status', 'nonaktif')->count(),
            ],
        ];
    }
}
