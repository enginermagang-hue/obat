<?php

namespace App\Filament\Resources\OpnameStoks\Pages;

use App\Filament\Resources\OpnameStoks\OpnameStokResource;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Stokobat\Boxicons\Boxicon;

class ListOpnameStoks extends ListRecords
{
    protected static string $resource = OpnameStokResource::class;

    protected string $view = 'filament.pages.stok-opname.list-stok-opname';

    public ?string $search = null;

    public ?string $activeStatus = null;

    public ?string $filterTipe = null;

    public ?string $filterTanggalFrom = null;

    public ?string $filterTanggalTo = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Opname')
                ->icon(Boxicon::PlusCircle)
                ->modalWidth(Width::SixExtraLarge)
                ->modalIcon(Boxicon::PlusCircle)
                ->modalFooterActionsAlignment('end')
                ->modalSubmitActionLabel('Buat Stok Opname')
                ->modalSubmitAction(
                    fn (Action $action) => $action
                        ->label('Buat Stok Opname')
                        ->icon(Boxicon::PlusCircle)
                        ->iconPosition('after')
                )
                ->createAnother(false)
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $data['fasilitas_id'] = auth()->user()->fasilitas_kesehatan_id ?? null;

                    $tipe = $data['tipe'] ?? 'penyesuaian';
                    foreach ($data['items'] ?? [] as &$item) {
                        $item['selisih'] = match ($tipe) {
                            'stok_awal', 'stok_baru' => $item['stok_fisik'] ?? 0,
                            default => ($item['stok_fisik'] ?? 0) - ($item['stok_sistem'] ?? 0),
                        };
                    }

                    session()->flash('_opname_create_items', $data['items'] ?? []);

                    return $data;
                })
                ->after(function ($record): void {
                    $items = session()->get('_opname_create_items', []);

                    foreach ($items as $item) {
                        $record->details()->create([
                            'obat_id' => $item['obat_id'],
                            'batch_id' => $item['batch_id'] ?? null,
                            'stok_sistem' => $item['stok_sistem'] ?? 0,
                            'stok_fisik' => $item['stok_fisik'] ?? 0,
                            'selisih' => $item['selisih'] ?? 0,
                            'batch_number' => $item['batch_number'] ?? null,
                            'tanggal_expired' => $item['tanggal_expired'] ?? null,
                            'keterangan' => null,
                        ]);
                    }

                    if ($record->status === 'selesai') {
                        app(StokService::class)->prosesOpnameSelesai($record);
                    }

                    session()->forget('_opname_create_items');
                })
                ->successRedirectUrl(fn () => OpnameStokResource::getUrl('index')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        $query->when($this->search, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('nomor_opname', 'like', "%{$v}%")
                ->orWhere('catatan', 'like', "%{$v}%");
        }));

        $query->when($this->activeStatus, fn (Builder $q, string $v) => $q->where('status', $v));
        $query->when($this->filterTipe, fn (Builder $q, string $v) => $q->where('tipe', $v));
        $query->when($this->filterTanggalFrom, fn (Builder $q, string $v) => $q->whereDate('tanggal_opname', '>=', $v));
        $query->when($this->filterTanggalTo, fn (Builder $q, string $v) => $q->whereDate('tanggal_opname', '<=', $v));

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

    public function updatedFilterTanggalFrom(): void
    {
        $this->resetTable();
    }

    public function updatedFilterTanggalTo(): void
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
            'filterTipe',
            'filterTanggalFrom',
            'filterTanggalTo',
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

        $tipeOptions = [
            'penyesuaian' => 'Penyesuaian',
            'stok_awal' => 'Stok Awal',
            'stok_baru' => 'Stok Baru',
        ];

        $tipeCounts = [];
        foreach ($tipeOptions as $key => $label) {
            $tipeCounts[$key] = (clone $query)->where('tipe', $key)->count();
        }

        return [
            'statusCounts' => $statusCounts,
            'tipeOptions' => $tipeOptions,
            'tipeCounts' => $tipeCounts,
        ];
    }
}
