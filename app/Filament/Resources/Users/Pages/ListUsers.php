<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.pages.user.list-user';

    public ?string $search = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pengguna')
                ->icon(Boxicon::PlusCircle)
                ->modalHeading('Tambah Pengguna')
                ->modalDescription('Tambahkan pengguna baru')
                ->modalIcon(Boxicon::PlusCircle)
                ->modalWidth(Width::Medium)
                ->modalFooterActionsAlignment('end')
                ->modalSubmitActionLabel('Tambah')
                ->createAnother(false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%");
        }));

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }
}
