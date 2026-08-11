<?php

namespace App\Filament\Resources\StokFaskes\Pages;

use App\Filament\Resources\StokFaskes\StokFaskesResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Services\KalkulasiStokMinimumService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListStokFaskes extends ListRecords
{
    protected static string $resource = StokFaskesResource::class;

    protected string $view = 'filament.pages.stok-faskes.list-stok-faskes';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterKategori = null;

    public ?string $filterFaskesId = null;

    public bool $isDinasUser = false;

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        $this->isDinasUser = ! filled($user->fasilitas_kesehatan_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kalkulasiStokMinimum')
                ->label('Kalkulasi Ulang Stok Minimum')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kalkulasi Ulang Stok Minimum')
                ->modalDescription('Hitung ulang stok_minimum untuk gudang (dinas) & faskes berdasarkan rata-rata distribusi/pemakaian 6 bulan terakhir.')
                ->modalSubmitActionLabel('Ya, Kalkulasi')
                ->action(function (KalkulasiStokMinimumService $service): void {
                    $result = $service->kalkulasiSemua();

                    Notification::make()
                        ->title('Stok Minimum Berhasil Dikalkulasi Ulang')
                        ->body("Gudang: {$result['gudang']} obat diperbarui. Faskes: {$result['faskes']} record diperbarui.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->whereHas('obat', function (Builder $q) use ($v) {
                $q->where('kode_obat', 'like', "%{$v}%")
                    ->orWhere('nama_obat', 'like', "%{$v}%");
            });
        }));

        $query->when($this->activeStatus, function (Builder $q, string $v) {
            match ($v) {
                'habis' => $q->where('jumlah', 0),
                'menipis' => $q->where('jumlah', '>', 0)->whereColumn('jumlah', '<=', 'stok_minimum'),
                'tersedia' => $q->where(function (Builder $q) {
                    $q->where('jumlah', '>', 0)->where(function (Builder $q2) {
                        $q2->whereColumn('jumlah', '>', 'stok_minimum')->orWhere('stok_minimum', 0);
                    });
                }),
                default => null,
            };
        });

        $query->when($this->filterKategori, fn (Builder $q, string $v) => $q->whereHas('obat', fn (Builder $q) => $q->where('kategori', $v)));
        $query->when($this->filterFaskesId, fn (Builder $q, string $v) => $q->where('fasilitas_id', $v));

        return $query;
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterKategori(): void
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
            'filterKategori',
            'filterFaskesId',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $statusCounts = [
            'habis' => (clone $query)->where('jumlah', 0)->count(),
            'menipis' => (clone $query)->where('jumlah', '>', 0)->whereColumn('jumlah', '<=', 'stok_minimum')->count(),
            'tersedia' => (clone $query)->where(function (Builder $q) {
                $q->where('jumlah', '>', 0)->where(function (Builder $q2) {
                    $q2->whereColumn('jumlah', '>', 'stok_minimum')->orWhere('stok_minimum', 0);
                });
            })->count(),
        ];

        return [
            'statusCounts' => $statusCounts,
            'kategoriOptions' => Obat::distinct()->pluck('kategori', 'kategori')->toArray(),
            'faskesOptions' => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id')->toArray(),
        ];
    }
}
