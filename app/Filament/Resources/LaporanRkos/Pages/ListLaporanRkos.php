<?php

namespace App\Filament\Resources\LaporanRkos\Pages;

use App\Filament\Resources\LaporanRkos\LaporanRkoResource;
use App\Models\LaporanRko;
use App\Models\PengaturanLaporan;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListLaporanRkos extends ListRecords
{
    protected static string $resource = LaporanRkoResource::class;

    protected static ?string $title = 'RKO (Rencana Kebutuhan Obat)';

    protected string $view = 'filament.pages.rko.list-rko';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTahun = null;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $actions = [];

        if (filled($user->fasilitas_kesehatan_id) && ! $user->hasRole('admin_gudang') && ! $user->hasRole('admin_dinas')) {
            $aksesDibuka = PengaturanLaporan::get('rko', 'akses_dibuka', $user->fasilitas_kesehatan_id);
            $periodeRkoTahun = PengaturanLaporan::get('rko', 'periode_tahun', $user->fasilitas_kesehatan_id);

            $sudahAdaRko = filled($periodeRkoTahun) && LaporanRko::where('fasilitas_id', $user->fasilitas_kesehatan_id)
                ->where('periode_tahun', (int) $periodeRkoTahun)
                ->exists();

            if ($aksesDibuka === '1' && ! $sudahAdaRko) {
                $actions[] = CreateAction::make()
                    ->label('Buat RKO')
                    ->icon(Boxicon::SolidPlusCircle);
            }
        }

        return $actions;
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_rko', 'like', "%{$v}%")
                ->orWhereHas('fasilitas', function (Builder $q) use ($v) {
                    $q->where('nama', 'like', "%{$v}%");
                });
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTahun, fn (Builder $q, string $v) => $q->where('periode_tahun', $v));

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
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $statusCounts = [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'diajukan' => (clone $query)->where('status', 'diajukan')->count(),
            'disetujui' => (clone $query)->where('status', 'disetujui')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];

        return [
            'statusCounts' => $statusCounts,
            'tahunOptions' => array_combine(
                range(now()->year - 2, now()->year + 2),
                range(now()->year - 2, now()->year + 2),
            ),
        ];
    }
}
