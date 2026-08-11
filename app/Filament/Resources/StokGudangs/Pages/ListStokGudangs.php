<?php

namespace App\Filament\Resources\StokGudangs\Pages;

use App\Filament\Resources\StokGudangs\StokGudangResource;
use App\Models\Obat;
use App\Services\KalkulasiStokMinimumService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStokGudangs extends ListRecords
{
    protected static string $resource = StokGudangResource::class;

    protected string $view = 'filament.pages.stok-gudang.list-stok-gudang';

    public ?string $search = null;

    public ?string $activeTab = 'all';

    public ?string $filterKategori = null;

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

        $query = $this->applyTabFilter($query, $this->activeTab);

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->whereHas('obat', function (Builder $q) use ($v) {
                $q->where('kode_obat', 'like', "%{$v}%")
                    ->orWhere('nama_obat', 'like', "%{$v}%");
            });
        }));

        $query->when($this->filterKategori, fn (Builder $q, string $v) => $q->whereHas('obat', fn (Builder $q) => $q->where('kategori', $v)));

        return $query;
    }

    public function filterByTab(?string $tab): void
    {
        $this->activeTab = $tab ?: 'all';
        $this->resetTable();
    }

    public function updatedFilterKategori(): void
    {
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeTab',
            'filterKategori',
        ]);
        $this->activeTab = 'all';
        $this->resetTable();
    }

    private function applyTabFilter(Builder $query, ?string $tab): Builder
    {
        if ($tab === 'habis') {
            $query->where('jumlah', 0);
        } elseif ($tab === 'hampir_habis') {
            $query->where('jumlah', '>', 0)->whereColumn('jumlah', '<=', 'stok_minimum');
        } elseif ($tab === 'kadaluarsa') {
            $query->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('batch_stok')
                    ->whereColumn('batch_stok.obat_id', 'stok_gudang.obat_id')
                    ->whereNull('batch_stok.fasilitas_id')
                    ->where('batch_stok.tanggal_expired', '<', now())
                    ->where('batch_stok.jumlah', '>', 0);
            });
        } elseif ($tab === 'hampir_kadaluarsa') {
            $query->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('batch_stok')
                    ->whereColumn('batch_stok.obat_id', 'stok_gudang.obat_id')
                    ->whereNull('batch_stok.fasilitas_id')
                    ->where('batch_stok.tanggal_expired', '>=', now())
                    ->where('batch_stok.tanggal_expired', '<=', now()->addMonths(3))
                    ->where('batch_stok.jumlah', '>', 0);
            });
        }

        return $query;
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $tabCounts = [
            'all' => (clone $query)->count(),
            'habis' => $this->applyTabFilter((clone $query), 'habis')->count(),
            'hampir_habis' => $this->applyTabFilter((clone $query), 'hampir_habis')->count(),
            'kadaluarsa' => $this->applyTabFilter((clone $query), 'kadaluarsa')->count(),
            'hampir_kadaluarsa' => $this->applyTabFilter((clone $query), 'hampir_kadaluarsa')->count(),
        ];

        return [
            'tabCounts' => $tabCounts,
            'kategoriOptions' => Obat::distinct()->pluck('kategori', 'kategori')->toArray(),
        ];
    }
}
