<?php

namespace App\Filament\Resources\NeracaTahunans\Pages;

use App\Filament\Resources\NeracaTahunans\NeracaTahunanResource;
use App\Models\FasilitasKesehatan;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListNeracaTahunans extends ListRecords
{
    protected static string $resource = NeracaTahunanResource::class;

    protected string $view = 'filament.pages.neraca-tahunan.list-neraca-tahunan';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTahun = null;

    public ?string $filterFaskesId = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Neraca Tahunan')
                ->icon(Boxicon::PlusCircle)
                ->visible(fn (): bool => auth()->user()?->can('create_neraca_tahunan') ?? false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_neraca', 'like', "%{$v}%")
                ->orWhereHas('fasilitas', function (Builder $q) use ($v) {
                    $q->where('nama', 'like', "%{$v}%");
                });
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTahun, fn (Builder $q, string $v) => $q->where('tahun', $v));
        $query->when($this->filterFaskesId, fn (Builder $q, string $v) => $q->where('fasilitas_id', $v));

        return $query;
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTahun(): void
    {
        $this->resetTable();
    }

    public function updatedFilterFaskesId(): void
    {
        $this->resetTable();
    }

    public function filterByStatus(?string $status): void
    {
        $this->activeStatus = $status ?: null;
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTahun',
            'filterFaskesId',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $statusCounts = [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
        ];

        return [
            'statusCounts' => $statusCounts,
            'tahunOptions' => array_combine(
                range(now()->year - 2, now()->year + 1),
                range(now()->year - 2, now()->year + 1),
            ),
            'faskesOptions' => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id')->toArray(),
        ];
    }
}
