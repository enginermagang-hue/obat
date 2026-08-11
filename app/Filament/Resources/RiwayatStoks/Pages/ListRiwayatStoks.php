<?php

namespace App\Filament\Resources\RiwayatStoks\Pages;

use App\Filament\Resources\RiwayatStoks\RiwayatStokResource;
use App\Models\FasilitasKesehatan;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListRiwayatStoks extends ListRecords
{
    protected static string $resource = RiwayatStokResource::class;

    protected string $view = 'filament.pages.riwayat-stok.list-riwayat-stok';

    public ?string $search = null;

    public ?string $filterFaskesId = '0';

    public array $filterTipe = [];

    public ?string $filterTanggalFrom = null;

    public ?string $filterTanggalTo = null;

    public bool $isDinasUser = false;

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        $this->isDinasUser = $user->hasAnyRole(['super_admin', 'admin_dinas', 'admin_gudang']);
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $user = Auth::user();

        if ($user->hasAnyRole(['puskesmas', 'pustu']) && filled($user->fasilitas_kesehatan_id)) {
            $query->where('fasilitas_id', $user->fasilitas_kesehatan_id);
        }

        if ($this->isDinasUser && $this->filterFaskesId !== null) {
            if ($this->filterFaskesId === '0') {
                $query->whereNull('fasilitas_id');
            } else {
                $query->where('fasilitas_id', $this->filterFaskesId);
            }
        }

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('keterangan', 'like', "%{$v}%")
                ->orWhereHas('obat', function (Builder $q) use ($v) {
                    $q->where('kode_obat', 'like', "%{$v}%")
                        ->orWhere('nama_obat', 'like', "%{$v}%");
                });
        }));

        $query->when($this->filterTipe, fn (Builder $q, array $v) => $q->whereIn('tipe', $v));
        $query->when($this->filterTanggalFrom, fn (Builder $q, string $v) => $q->whereDate('tanggal', '>=', $v));
        $query->when($this->filterTanggalTo, fn (Builder $q, string $v) => $q->whereDate('tanggal', '<=', $v));

        return $query;
    }

    public function updatedFilterFaskesId(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTipe(): void
    {
        $this->resetTable();
    }

    public function applyFilterTipe(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggalFrom(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggalTo(): void
    {
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'filterFaskesId',
            'filterTipe',
            'filterTanggalFrom',
            'filterTanggalTo',
        ]);
        $this->filterFaskesId = '0';
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $faskesOptions = collect(FasilitasKesehatan::pluck('nama', 'id'))
            ->prepend('Dinas (Pusat)', '0')
            ->toArray();

        $tipeOptions = [
            'masuk' => 'Masuk',
            'keluar' => 'Keluar',
            'distribusi_masuk' => 'Distribusi Masuk',
            'distribusi_keluar' => 'Distribusi Keluar',
            'rusak' => 'Rusak',
            'hilang' => 'Hilang',
            'expired' => 'Expired',
            'opname' => 'Opname',
            'penyesuaian' => 'Penyesuaian',
        ];

        $tipeCounts = [];
        foreach ($tipeOptions as $key => $label) {
            $tipeCounts[$key] = (clone $query)->where('tipe', $key)->count();
        }

        return [
            'faskesOptions' => $faskesOptions,
            'tipeOptions' => $tipeOptions,
            'tipeCounts' => $tipeCounts,
        ];
    }
}
