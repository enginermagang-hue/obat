<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'Hak Akses Pengguna';

    protected string $view = 'filament.pages.role.list-role';

    public ?string $search = null;

    public function getBreadcrumbs(): array
    {
        return [
            '/admin' => 'Home',
            '/roles' => 'Hak Akses Pengguna',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Role')
                ->icon(Boxicon::PlusCircle),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('name', 'like', "%{$v}%")
                ->orWhere('guard_name', 'like', "%{$v}%");
        }));

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }
}
