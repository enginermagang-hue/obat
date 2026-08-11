<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected string $view = 'filament.pages.activity-log.list-activity-log';

    public ?string $search = null;

    public ?string $filterLogName = null;

    public ?string $filterEvent = null;

    public ?string $filterTanggalFrom = null;

    public ?string $filterTanggalTo = null;

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('description', 'like', "%{$v}%")
                ->orWhere('log_name', 'like', "%{$v}%")
                ->orWhere('event', 'like', "%{$v}%")
                ->orWhereHas('causer', fn (Builder $q) => $q->where('name', 'like', "%{$v}%"));
        }));

        $query->when($this->filterLogName, fn (Builder $q, string $v) => $q->where('log_name', $v));
        $query->when($this->filterEvent, fn (Builder $q, string $v) => $q->where('event', $v));
        $query->when($this->filterTanggalFrom, fn (Builder $q, string $v) => $q->whereDate('created_at', '>=', $v));
        $query->when($this->filterTanggalTo, fn (Builder $q, string $v) => $q->whereDate('created_at', '<=', $v));

        return $query;
    }

    public function updatedFilterLogName(): void
    {
        $this->resetTable();
    }

    public function updatedFilterEvent(): void
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
            'filterLogName',
            'filterEvent',
            'filterTanggalFrom',
            'filterTanggalTo',
        ]);
        $this->resetTable();
    }

    protected function getViewData(): array
    {
        $query = static::getResource()::getEloquentQuery();

        $logNameOptions = [
            'auth' => 'Auth',
            'master_data' => 'Master Data',
            'permintaan_obat' => 'Permintaan Obat',
            'distribusi_obat' => 'Distribusi Obat',
            'retur_obat' => 'Retur Obat',
            'penerimaan_stok' => 'Penerimaan Stok',
            'opname_stok' => 'Opname Stok',
            'laporan_lplpo' => 'Laporan LPLPO',
            'laporan_rko' => 'Laporan RKO',
            'laporan_neraca' => 'Neraca Tahunan',
            'user_management' => 'User Management',
        ];

        $eventOptions = [
            'login' => 'Login',
            'logout' => 'Logout',
            'failed_login' => 'Gagal Login',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'received' => 'Received',
            'generated' => 'Generated',
            'role_updated' => 'Role Updated',
        ];

        return [
            'logNameOptions' => $logNameOptions,
            'eventOptions' => $eventOptions,
        ];
    }
}
