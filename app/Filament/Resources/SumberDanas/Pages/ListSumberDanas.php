<?php

namespace App\Filament\Resources\SumberDanas\Pages;

use App\Filament\Resources\SumberDanas\SumberDanaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListSumberDanas extends ListRecords
{
    protected static string $resource = SumberDanaResource::class;

    protected ?string $subheading = 'Kelola Sumber Dana Dinas';

    protected string $view = 'filament.pages.sumber-dana.list-sumber-dana';

    public ?string $search = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Sumber Dana')
                ->icon(Boxicon::PlusCircle)
                ->modalIcon(Boxicon::PlusCircle)
                ->modalHeading('Tambah Sumber Dana')
                ->modalWidth(Width::Medium)
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalSubmitActionLabel('Tambah')
                ->createAnother(false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('kode', 'like', "%{$v}%")
                ->orWhere('nama', 'like', "%{$v}%")
                ->orWhere('keterangan', 'like', "%{$v}%");
        }));

        return $query;
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }
}
