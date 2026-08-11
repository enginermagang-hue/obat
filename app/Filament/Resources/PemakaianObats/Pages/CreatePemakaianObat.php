<?php

namespace App\Filament\Resources\PemakaianObats\Pages;

use App\Filament\Resources\PemakaianObats\Concerns\ManagesPemakaianDetails;
use App\Filament\Resources\PemakaianObats\PemakaianObatResource;
use App\Models\PemakaianObat;
use App\Services\StokService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;

class CreatePemakaianObat extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable {
        InteractsWithTable::table as protected filamentTable;
    }
    use ManagesPemakaianDetails {
        ManagesPemakaianDetails::table insteadof InteractsWithTable;
    }

    protected static string $resource = PemakaianObatResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['user_id'] = $user?->id;

        if (blank($data['fasilitas_id'] ?? null) || ! $user?->hasRole('super_admin')) {
            $data['fasilitas_id'] = $user?->fasilitas_kesehatan_id;
        }

        if (blank($data['tanggal_pemakaian'] ?? null)) {
            $data['tanggal_pemakaian'] = now()->format('Y-m-d');
        }

        if (blank($data['nomor_pemakaian'] ?? null)) {
            $data['nomor_pemakaian'] = PemakaianObat::generateNomorPemakaian(
                $data['tanggal_pemakaian'],
                $data['fasilitas_id'],
            );
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (empty($this->details)) {
            Notification::make()
                ->title('Pemakaian Disimpan (Tanpa Detail)')
                ->body('Pemakaian tersimpan tanpa item obat. Stok tidak berubah.')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->details as $detail) {
            $record->details()->create([
                'obat_id' => $detail['obat_id'],
                'batch_id' => $detail['batch_id'] ?? null,
                'jumlah' => $detail['jumlah'],
                'catatan' => $detail['catatan'] ?? null,
            ]);
        }

        app(StokService::class)->prosesPemakaian($record->fresh('details'));

        $totalItems = count($this->details);
        $totalQty = collect($this->details)->sum('jumlah');

        Notification::make()
            ->title('Pemakaian Obat Tercatat')
            ->body("{$totalItems} item obat ({$totalQty} unit) telah dipakai. Stok berkurang otomatis.")
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
            $this->getCreateFormAction()
                ->label('Simpan Pemakaian')
                ->icon('heroicon-m-document-check')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pemakaian Obat')
                ->modalDescription(fn (): string => $this->getSummaryForConfirm())
                ->modalSubmitActionLabel('Ya, Simpan')
                ->disabled(fn (): bool => empty($this->details))
                ->tooltip(fn (): ?string => empty($this->details)
                    ? 'Tambahkan minimal 1 item obat terlebih dahulu'
                    : null),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
