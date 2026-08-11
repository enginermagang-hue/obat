<?php

namespace App\Filament\Resources\ReturObats\Pages;

use App\Filament\Forms\Components\DateRangeFilter;
use App\Filament\Forms\Components\SearchInput;
use App\Filament\Resources\ReturObats\ReturObatResource;
use App\Models\FasilitasKesehatan;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class ListReturObats extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ReturObatResource::class;

    protected string $view = 'filament.pages.retur-obat.list-retur-obat';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTipe = null;

    public ?string $filterAlasan = null;

    public ?int $filterPengirim = null;

    public ?int $filterPenerima = null;

    public ?array $filterTanggal = ['from' => null, 'to' => null];

    public bool $isDinasUser = false;

    protected function getForms(): array
    {
        return ['form', 'filtersForm'];
    }

    public function mount(): void
    {
        parent::mount();

        $this->isDinasUser = Auth::user()->hasAnyRole(['super_admin', 'admin_dinas', 'admin_gudang']);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('activeStatus')
                            ->label('Status')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua')
                            ->options(fn ($component): array => [
                                'draft' => 'Draft ('.$component->getLivewire()->statusCounts()['draft'].')',
                                'menunggu_approval' => 'Menunggu Approval ('.$component->getLivewire()->statusCounts()['menunggu_approval'].')',
                                'disetujui' => 'Disetujui ('.$component->getLivewire()->statusCounts()['disetujui'].')',
                                'ditolak' => 'Ditolak ('.$component->getLivewire()->statusCounts()['ditolak'].')',
                                'dalam_pengiriman' => 'Dalam Pengiriman ('.$component->getLivewire()->statusCounts()['dalam_pengiriman'].')',
                                'diterima' => 'Diterima ('.$component->getLivewire()->statusCounts()['diterima'].')',
                                'selesai' => 'Selesai ('.$component->getLivewire()->statusCounts()['selesai'].')',
                            ]),
                        Select::make('filterTipe')
                            ->label('Tipe')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Tipe')
                            ->options(fn ($component): array => [
                                'puskesmas_ke_gudang' => 'Puskesmas \u2192 Gudang ('.$component->getLivewire()->tipeCounts()['puskesmas_ke_gudang'].')',
                                'pustu_ke_puskesmas' => 'Pustu \u2192 Puskesmas ('.$component->getLivewire()->tipeCounts()['pustu_ke_puskesmas'].')',
                                'gudang_ke_supplier' => 'Gudang \u2192 Supplier ('.$component->getLivewire()->tipeCounts()['gudang_ke_supplier'].')',
                            ]),
                        Select::make('filterAlasan')
                            ->label('Alasan')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Alasan')
                            ->options(fn ($component): array => [
                                'expired' => 'Kedaluwarsa ('.$component->getLivewire()->alasanCounts()['expired'].')',
                                'rusak' => 'Rusak ('.$component->getLivewire()->alasanCounts()['rusak'].')',
                                'kelebihan_stok' => 'Kelebihan Stok ('.$component->getLivewire()->alasanCounts()['kelebihan_stok'].')',
                                'salah_kirim' => 'Salah Kirim ('.$component->getLivewire()->alasanCounts()['salah_kirim'].')',
                                'recall' => 'Recall ('.$component->getLivewire()->alasanCounts()['recall'].')',
                                'near_expiry' => 'Mendekati Exp ('.$component->getLivewire()->alasanCounts()['near_expiry'].')',
                                'lainnya' => 'Lainnya ('.$component->getLivewire()->alasanCounts()['lainnya'].')',
                            ]),
                        DateRangeFilter::make('filterTanggal')
                            ->label('Tanggal')
                            ->btnLabel('Rentang Tanggal Retur')
                            ->live(),
                        Select::make('filterPengirim')
                            ->label('Pengirim')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Pengirim')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => $component->getLivewire()->isDinasUser),
                        Select::make('filterPenerima')
                            ->label('Penerima')
                            ->native(false)
                            ->live()
                            ->placeholder('Semua Penerima')
                            ->options(fn (): Collection => FasilitasKesehatan::orderBy('nama')->pluck('nama', 'id'))
                            ->visible(fn ($component): bool => $component->getLivewire()->isDinasUser),
                        SearchInput::make('search')
                            ->placeholder('Cari nomor retur, alasan...')
                            ->hiddenLabel()
                            ->live()
                            ->debounce(300)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function statusCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'menunggu_approval' => (clone $query)->where('status', 'menunggu_approval')->count(),
            'disetujui' => (clone $query)->where('status', 'disetujui')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
            'dalam_pengiriman' => (clone $query)->where('status', 'dalam_pengiriman')->count(),
            'diterima' => (clone $query)->where('status', 'diterima')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
        ];
    }

    public function tipeCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'puskesmas_ke_gudang' => (clone $query)->where('tipe_retur', 'puskesmas_ke_gudang')->count(),
            'pustu_ke_puskesmas' => (clone $query)->where('tipe_retur', 'pustu_ke_puskesmas')->count(),
            'gudang_ke_supplier' => (clone $query)->where('tipe_retur', 'gudang_ke_supplier')->count(),
        ];
    }

    public function alasanCounts(): array
    {
        $query = static::getResource()::getEloquentQuery();

        return [
            'expired' => (clone $query)->where('alasan', 'expired')->count(),
            'rusak' => (clone $query)->where('alasan', 'rusak')->count(),
            'kelebihan_stok' => (clone $query)->where('alasan', 'kelebihan_stok')->count(),
            'salah_kirim' => (clone $query)->where('alasan', 'salah_kirim')->count(),
            'recall' => (clone $query)->where('alasan', 'recall')->count(),
            'near_expiry' => (clone $query)->where('alasan', 'near_expiry')->count(),
            'lainnya' => (clone $query)->where('alasan', 'lainnya')->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Retur Obat')
                ->icon(Boxicon::PlusCircle),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_retur', 'like', "%{$v}%")
                ->orWhere('alasan', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%");
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTipe, fn (Builder $q, string $v) => $q->where('tipe_retur', $v));
        $query->when($this->filterAlasan, fn (Builder $q, string $v) => $q->where('alasan', $v));
        $query->when($this->filterPengirim, fn (Builder $q, int $v) => $q->where('fasilitas_pengirim_id', $v));
        $query->when($this->filterPenerima, fn (Builder $q, int $v) => $q->where('fasilitas_penerima_id', $v));
        $query->when($this->filterTanggal['from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_retur', '>=', $v));
        $query->when($this->filterTanggal['to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('tanggal_retur', '<=', $v));

        return $query;
    }

    public function updatedActiveStatus(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTipe(): void
    {
        $this->resetTable();
    }

    public function updatedFilterAlasan(): void
    {
        $this->resetTable();
    }

    public function updatedFilterPengirim(): void
    {
        $this->resetTable();
    }

    public function updatedFilterPenerima(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggal(): void
    {
        $this->resetTable();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'activeStatus',
            'filterTipe',
            'filterAlasan',
            'filterPengirim',
            'filterPenerima',
            'filterTanggal',
        ]);
        $this->resetTable();
    }
}
