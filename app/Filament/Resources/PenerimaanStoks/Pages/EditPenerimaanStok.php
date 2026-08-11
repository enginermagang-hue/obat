<?php

namespace App\Filament\Resources\PenerimaanStoks\Pages;

use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Models\PenerimaanStok;
use App\Services\NotificationService;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Colors\Color;
use Illuminate\Support\Collection;
use Stokobat\Boxicons\Boxicon;

class EditPenerimaanStok extends EditRecord implements HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static string $resource = PenerimaanStokResource::class;

    private ?string $actionStatus = null;

    private ?string $previousStatus = null;

    private ?Collection $previousDetails = null;

    protected function getSimpanDraftAction()
    {
        return Action::make('simpanDraft')
            ->label('Simpan')
            ->icon('heroicon-m-document-check')
            ->color('gray')
            ->action(fn () => $this->prosesSimpan('draft'))
            ->extraAttributes([
                'class' => 'ml-auto',
            ]);
    }

    protected function getSimpanKonfirmasiAction()
    {
        return Action::make('simpanKonfirmasi')
            ->label('Konfirmasi')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->visible(fn (): bool => $this->record->status === 'draft')
            ->action(fn () => $this->prosesSimpan('dikonfirmasi'));
    }

    protected function getDeleteAction()
    {
        return Action::make('delete')
            ->label('Hapus')
            ->icon(Boxicon::Trash)
            ->color(Color::Red)
            ->requiresConfirmation()
            ->action(function (): void {
                $this->record->delete();
                $this->redirect(static::getResource()::getUrl('index'));
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getDeleteAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
            $this->getSimpanDraftAction(),
            $this->getSimpanKonfirmasiAction(),
        ];
    }

    protected function prosesSimpan(string $status): void
    {
        $this->actionStatus = $status;
        $this->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousStatus = $this->record->getOriginal('status');
        $this->previousDetails = $this->record->details()->get();

        $data['status'] = $this->actionStatus ?? $this->record->status;

        if (($data['tipe'] ?? null) === 'manual') {
            $data['distribusi_id'] = null;
        }

        foreach ($this->data['details'] ?? [] as $i => $d) {
            $hargaRaw = $d['harga_satuan'] ?? null;
            if (filled($hargaRaw)) {
                $hargaStr = (string) $hargaRaw;
                $hargaNum = str_contains($hargaStr, ',')
                    ? (float) str_replace(['.', ','], ['', '.'], $hargaStr)
                    : (float) $hargaStr;
            } else {
                $hargaNum = null;
            }
            $jumlah = (int) ($d['jumlah'] ?? 0);
            $this->data['details'][$i]['sub_total'] = $hargaNum !== null ? $jumlah * $hargaNum : null;
        }

        if (($data['tipe'] ?? $this->record->tipe) === 'pembelian') {
            $totalBiaya = 0.0;
            foreach ($this->data['details'] ?? [] as $d) {
                $totalBiaya += (float) ($d['sub_total'] ?? 0);
            }
            $data['total_biaya'] = $totalBiaya > 0 ? round($totalBiaya, 2) : null;
        }

        unset($data['details']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $stokService = app(StokService::class);

        if ($this->previousStatus === 'dikonfirmasi' && $record->status !== 'dikonfirmasi') {
            if ($record->tipe === 'distribusi') {
                $record->distribusi?->update([
                    'status' => 'dalam_pengiriman',
                    'tanggal_terima' => null,
                    'penerima_id' => null,
                    'penerimaan_stok_id' => null,
                ]);
                if ($record->distribusi?->permintaan) {
                    $record->distribusi->permintaan->update([
                        'status' => 'sedang_didistribusi',
                        'tanggal_diterima' => null,
                    ]);
                }
                $stokService->reversePenerimaan($record, $this->previousDetails);
            } else {
                $stokService->reversePenerimaan($record, $this->previousDetails);
            }
        }

        if ($record->status === 'dikonfirmasi' && $this->previousStatus !== 'dikonfirmasi') {
            if ($record->tipe === 'distribusi') {
                $stokService->prosesPenerimaanDistribusi($record);
            } else {
                $stokService->prosesPenerimaan($record);
            }

            $this->notifyDikonfirmasi($record);
        }
    }

    private function notifyDikonfirmasi(PenerimaanStok $record): void
    {
        $url = PenerimaanStokResource::getUrl('view', ['record' => $record->id]);

        app(NotificationService::class)->notifyRole(
            'admin_gudang',
            'Penerimaan Stok Dikonfirmasi',
            "Penerimaan {$record->nomor_penerimaan} telah dikonfirmasi dan stok diperbarui.",
            $url,
            icon: 'heroicon-o-inbox-arrow-down',
            color: 'success',
        );

        if ($record->tipe === 'distribusi' && $record->distribusi?->fasilitas_pengirim_id) {
            app(NotificationService::class)->notifyFaskesUsers(
                $record->distribusi->fasilitas_pengirim_id,
                'Distribusi Obat Diterima',
                "Distribusi Anda telah diterima melalui penerimaan {$record->nomor_penerimaan}.",
                $url,
                icon: 'heroicon-o-inbox-arrow-down',
                color: 'success',
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }
}
