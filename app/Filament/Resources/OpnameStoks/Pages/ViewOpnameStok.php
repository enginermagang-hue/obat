<?php

namespace App\Filament\Resources\OpnameStoks\Pages;

use App\Filament\Resources\OpnameStoks\OpnameStokResource;
use App\Models\DetailOpnameStok;
use App\Models\OpnameStok;
use App\Services\StokService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewOpnameStok extends ViewRecord
{
    protected static string $resource = OpnameStokResource::class;

    protected string $view = 'filament.pages.detail-opname-stok';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'details.obat',
            'fasilitas',
            'user',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit Opname')
                ->modalWidth(Width::ExtraLarge)
                ->visible(fn (): bool => $this->record?->status === 'draft')
                ->mutateRecordDataUsing(function (array $data, OpnameStok $record): array {
                    $data['items'] = $record->details->map(fn (DetailOpnameStok $detail): array => [
                        'id' => $detail->id,
                        'obat_id' => $detail->obat_id,
                        'batch_id' => $detail->batch_id,
                        'stok_sistem' => $detail->stok_sistem,
                        'stok_fisik' => $detail->stok_fisik,
                        'selisih' => $detail->selisih,
                        'batch_number' => $detail->batch_number,
                        'tanggal_expired' => $detail->tanggal_expired?->format('Y-m-d'),
                    ])->toArray();

                    return $data;
                })
                ->mutateDataUsing(function (array $data, OpnameStok $record): array {
                    $tipe = $data['tipe'] ?? 'penyesuaian';
                    foreach ($data['items'] ?? [] as &$item) {
                        $item['selisih'] = match ($tipe) {
                            'stok_awal', 'stok_baru' => $item['stok_fisik'] ?? 0,
                            default => ($item['stok_fisik'] ?? 0) - ($item['stok_sistem'] ?? 0),
                        };
                    }

                    session()->flash('_opname_prev_status', $record->getOriginal('status'));
                    session()->flash('_opname_prev_details', $record->details()->get());

                    return $data;
                })
                ->after(function (OpnameStok $record, array $data): void {
                    $previousStatus = session()->get('_opname_prev_status');
                    $previousDetails = session()->get('_opname_prev_details');

                    $items = $data['items'] ?? [];

                    $existingIds = collect($items)->pluck('id')->filter()->toArray();
                    $record->details()->whereNotIn('id', $existingIds)->delete();

                    foreach ($items as $item) {
                        $selisih = match ($record->tipe) {
                            'stok_awal', 'stok_baru' => (int) ($item['stok_fisik'] ?? 0),
                            default => (int) ($item['stok_fisik'] ?? 0) - (int) ($item['stok_sistem'] ?? 0),
                        };

                        $detailData = [
                            'obat_id' => $item['obat_id'],
                            'batch_id' => $item['batch_id'] ?? null,
                            'stok_sistem' => $item['stok_sistem'] ?? 0,
                            'stok_fisik' => $item['stok_fisik'] ?? 0,
                            'selisih' => $selisih,
                            'batch_number' => $item['batch_number'] ?? null,
                            'tanggal_expired' => $item['tanggal_expired'] ?? null,
                            'keterangan' => null,
                        ];

                        if (isset($item['id'])) {
                            $record->details()->where('id', $item['id'])->update($detailData);
                        } else {
                            $record->details()->create($detailData);
                        }
                    }

                    if ($previousStatus === 'selesai') {
                        app(StokService::class)->reverseOpname($record, $previousDetails);
                    }

                    if ($record->status === 'selesai') {
                        app(StokService::class)->prosesOpnameSelesai($record->fresh('details'));
                    }

                    session()->forget(['_opname_prev_status', '_opname_prev_details']);
                }),
        ];
    }

    public static function formatTipe(string $state): string
    {
        return match ($state) {
            'penyesuaian' => 'Penyesuaian',
            'stok_awal' => 'Stok Awal',
            'stok_baru' => 'Stok Baru',
            default => $state,
        };
    }

    public static function formatStatus(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'proses' => 'Proses',
            'selesai' => 'Selesai',
            default => $state,
        };
    }

    public static function statusBg(string $state): string
    {
        return match ($state) {
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            'proses' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'selesai' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    protected function getViewData(): array
    {
        $record = $this->record;

        $details = $record->details->map(fn ($detail) => [
            'obat_name' => $detail->obat->nama_obat ?? '',
            'stok_sistem' => $detail->stok_sistem,
            'stok_fisik' => $detail->stok_fisik,
            'selisih' => $detail->selisih,
            'batch_number' => $detail->batch_number,
            'tanggal_expired' => $detail->tanggal_expired?->format('d/m/Y'),
            'keterangan' => $detail->keterangan,
        ]);

        return [
            'record' => $record,
            'statusLabel' => static::formatStatus($record->status),
            'statusBg' => static::statusBg($record->status),
            'tipeLabel' => static::formatTipe($record->tipe),
            'details' => $details,
            'fasilitasLabel' => $record->fasilitas?->nama ?? 'GUDANG',
        ];
    }
}
