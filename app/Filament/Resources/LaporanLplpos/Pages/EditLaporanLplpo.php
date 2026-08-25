<?php

namespace App\Filament\Resources\LaporanLplpos\Pages;

use App\Filament\Resources\LaporanLplpos\Concerns\ManagesLplpoDetails;
use App\Filament\Resources\LaporanLplpos\LaporanLplpoResource;
use App\Services\LaporanLplpoService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class EditLaporanLplpo extends EditRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable {
        InteractsWithTable::table as protected filamentTable;
    }
    use ManagesLplpoDetails {
        ManagesLplpoDetails::table insteadof InteractsWithTable;
    }

    protected static string $resource = LaporanLplpoResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing('details.obat');

        $this->details = $this->record->details->map(fn ($detail, int $key) => [
            '_key' => $key,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat?->nama_obat ?? '',
            'satuan' => $detail->obat?->satuan ?? '',
            'stok_awal' => $detail->stok_awal,
            'jumlah_masuk' => $detail->jumlah_masuk,
            'persediaan' => $detail->stok_awal + $detail->jumlah_masuk,
            'jumlah_keluar' => $detail->jumlah_keluar,
            'sisa_stok' => $detail->sisa_stok,
            'stok_optimum' => $detail->stok_optimum,
            'permintaan_selanjutnya' => $detail->permintaan_selanjutnya,
            'keterangan' => $detail->keterangan,
        ])->values()->toArray();
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $existingIds = collect($this->details)->pluck('id')->filter()->toArray();
        $record->details()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->details as $detail) {
            $detailData = [
                'obat_id' => $detail['obat_id'],
                'stok_awal' => $detail['stok_awal'] ?? 0,
                'jumlah_masuk' => $detail['jumlah_masuk'] ?? 0,
                'jumlah_keluar' => $detail['jumlah_keluar'] ?? 0,
                'sisa_stok' => $detail['sisa_stok'] ?? 0,
                'stok_optimum' => $detail['stok_optimum'] ?? 0,
                'permintaan_selanjutnya' => $detail['permintaan_selanjutnya'] ?? 0,
                'keterangan' => $detail['keterangan'] ?? null,
            ];

            if (filled($detail['id'] ?? null)) {
                $record->details()->where('id', $detail['id'])->update($detailData);
            } else {
                $record->details()->create($detailData);
            }
        }
    }

    private function reloadDetails(): void
    {
        $this->record->loadMissing('details.obat');

        $this->details = $this->record->details->map(fn ($detail, int $key) => [
            '_key' => $key,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat?->nama_obat ?? '',
            'satuan' => $detail->obat?->satuan ?? '',
            'stok_awal' => $detail->stok_awal,
            'jumlah_masuk' => $detail->jumlah_masuk,
            'persediaan' => $detail->stok_awal + $detail->jumlah_masuk,
            'jumlah_keluar' => $detail->jumlah_keluar,
            'sisa_stok' => $detail->sisa_stok,
            'stok_optimum' => $detail->stok_optimum,
            'permintaan_selanjutnya' => $detail->permintaan_selanjutnya,
            'keterangan' => $detail->keterangan,
        ])->values()->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record?->status === 'draft')
                ->requiresConfirmation()
                ->modalDescription('Generate ulang akan menghapus semua detail yang sudah diisi dan membuat ulang dari riwayat stok. Lanjutkan?')
                ->action(function (): void {
                    app(LaporanLplpoService::class)->generate($this->record);
                    $this->reloadDetails();
                    Notification::make()
                        ->title('Detail LPLPO berhasil di-generate ulang')
                        ->success()
                        ->send();
                }),
            Action::make('cetak')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('admin.lplpo.cetak-pdf', ['lplpo' => $this->record->id]), shouldOpenInNewTab: true),
            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
