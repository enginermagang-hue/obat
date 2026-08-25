<?php

namespace App\Filament\Resources\PemakaianObats\Pages;

use App\Filament\Resources\PemakaianObats\Concerns\ManagesPemakaianDetails;
use App\Filament\Resources\PemakaianObats\PemakaianObatResource;
use App\Services\StokService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stokobat\Boxicons\Boxicon;

class EditPemakaianObat extends EditRecord implements HasSchemas, HasTable
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

    protected ?bool $hasDatabaseTransactions = true;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->canEditRecord()) {
            redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->record->loadMissing(['details.obat', 'details.batch']);

        $this->details = $this->record->details->map(fn ($detail, int $key) => [
            '_key' => $key,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat->nama_obat ?? '',
            'batch_id' => $detail->batch_id,
            'batch_number' => $detail->batch?->batch_number ?? '',
            'jumlah' => $detail->jumlah,
            'catatan' => $detail->catatan,
        ])->values()->toArray();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['tanggal_pemakaian']) && $data['tanggal_pemakaian'] !== $this->record->tanggal_pemakaian->format('Y-m-d')) {
            $data['tanggal_pemakaian'] = $this->record->tanggal_pemakaian->format('Y-m-d');
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $originalRecord = $this->record;

        app(StokService::class)->reversePemakaian($originalRecord->loadMissing('details'));

        $existingIds = collect($this->details)->pluck('id')->filter()->toArray();

        $record->details()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->details as $detail) {
            $detailData = [
                'obat_id' => $detail['obat_id'],
                'batch_id' => $detail['batch_id'] ?? null,
                'jumlah' => $detail['jumlah'],
                'catatan' => $detail['catatan'] ?? null,
            ];

            if (filled($detail['id'] ?? null)) {
                $record->details()->where('id', $detail['id'])->update($detailData);
            } else {
                $record->details()->create($detailData);
            }
        }

        $updatedRecord = $record->fresh('details');
        if ($updatedRecord->details->isNotEmpty()) {
            app(StokService::class)->prosesPemakaian($updatedRecord);

            Notification::make()
                ->title('Pemakaian Diperbarui')
                ->body('Stok telah disesuaikan ulang sesuai perubahan.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Pemakaian Diperbarui')
                ->body('Detail obat kosong, tidak ada perubahan stok.')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->icon(Boxicon::SolidTrash)
                ->record(fn (): ?Model => $this->getRecord())
                ->requiresConfirmation()
                ->modalHeading('Hapus Pemakaian Obat')
                ->modalDescription('Seluruh item obat dan stok akan dikembalikan. Tindakan ini tidak dapat dibatalkan.')
                ->visible(fn (): bool => $this->canEditRecord())
                ->using(function (Model $record): bool {
                    return DB::transaction(function () use ($record): bool {
                        app(StokService::class)->reversePemakaian($record->loadMissing('details'));

                        return $record->delete();
                    });
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction()
                ->icon(Boxicon::XCircle)
                ->label('Batal'),
            $this->getSaveFormAction()
                ->label('Simpan Perubahan')
                ->icon('heroicon-m-document-check')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Pemakaian')
                ->modalDescription(fn (): string => $this->getSummaryForConfirm())
                ->modalSubmitActionLabel('Ya, Simpan'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    private function canEditRecord(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $this->record->fasilitas_id === $userFaskesId
            && $this->record->tanggal_pemakaian?->isToday();
    }
}
