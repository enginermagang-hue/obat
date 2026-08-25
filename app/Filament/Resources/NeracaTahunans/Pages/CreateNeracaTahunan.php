<?php

namespace App\Filament\Resources\NeracaTahunans\Pages;

use App\Filament\Resources\NeracaTahunans\Concerns\ManagesNeracaDetails;
use App\Filament\Resources\NeracaTahunans\NeracaTahunanResource;
use App\Services\NeracaTahunanService;
use App\Services\NomorFormatService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Stokobat\Boxicons\Boxicon;

class CreateNeracaTahunan extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable {
        InteractsWithTable::table as protected filamentTable;
    }
    use ManagesNeracaDetails {
        ManagesNeracaDetails::table insteadof InteractsWithTable;
    }

    public string $statusToSave = 'draft';

    protected static string $resource = NeracaTahunanResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['dibuat_oleh'] = $user->id;
        $data['status'] = $this->statusToSave;

        if (blank($data['nomor_neraca'] ?? null)) {
            $data['nomor_neraca'] = $this->generateNomorNeraca($user->fasilitas_kesehatan_id);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (empty($this->details)) {
            Notification::make()
                ->title('Neraca Disimpan (Tanpa Detail)')
                ->body('Neraca tersimpan tanpa item obat.')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->details as $detail) {
            $created = $record->details()->create([
                'obat_id' => $detail['obat_id'],
                'stok_awal' => $detail['stok_awal'],
                'total_masuk' => $detail['total_masuk'],
                'total_keluar' => $detail['total_keluar'],
                'stok_akhir' => $detail['stok_akhir'],
                'stok_optimum' => $detail['stok_optimum'],
                'permintaan' => $detail['permintaan'],
                'harga_satuan' => $detail['harga_satuan'],
                'nilai_stok' => $detail['nilai_stok'],
                'keterangan' => $detail['keterangan'] ?? null,
            ]);

            app(NeracaTahunanService::class)->syncSumberDanaBreakdown($created);
        }

        $totalItems = count($this->details);
        $totalNilai = collect($this->details)->sum('nilai_stok');
        $statusLabel = $record->status === 'selesai' ? 'Selesai' : 'Draft';

        Notification::make()
            ->title("Neraca {$statusLabel} Disimpan")
            ->body("{$totalItems} item obat. Total nilai stok: Rp ".number_format($totalNilai, 0, ',', '.'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::X),
            Action::make('simpan')
                ->label('Simpan')
                ->icon('heroicon-m-document-check')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Simpan Neraca')
                ->modalDescription(fn (): string => $this->getSummaryForConfirm())
                ->modalSubmitActionLabel('Ya, Simpan')
                ->action(function (): void {
                    $this->statusToSave = 'draft';
                    $this->create();
                })
                ->disabled(fn (): bool => empty($this->details))
                ->tooltip(fn (): ?string => empty($this->details)
                    ? 'Tambahkan minimal 1 item obat terlebih dahulu'
                    : null),
            Action::make('selesai')
                ->label('Selesai')
                ->icon('heroicon-m-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penyelesaian Neraca')
                ->modalDescription(fn (): string => $this->getSummaryForConfirm())
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->action(function (): void {
                    $this->statusToSave = 'selesai';
                    $this->create();
                })
                ->disabled(fn (): bool => empty($this->details))
                ->tooltip(fn (): ?string => empty($this->details)
                    ? 'Tambahkan minimal 1 item obat terlebih dahulu'
                    : null),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFromStok')
                ->label('Generate dari Stok')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('Generate akan mengisi item neraca dari riwayat stok. Item yang sudah ada akan ditimpa.')
                ->action(function (): void {
                    $this->generateFromStok();

                    $total = count($this->details);

                    Notification::make()
                        ->title('Berhasil di-generate')
                        ->body("{$total} item obat dari riwayat stok")
                        ->success()
                        ->send();
                }),
        ];
    }

    private function generateNomorNeraca(?int $fasilitasId = null): string
    {
        return NomorFormatService::generate('neraca_tahunan', $fasilitasId);
    }

    protected function getSummaryForConfirm(): string
    {
        $totalItems = count($this->details);
        $totalNilai = collect($this->details)->sum('nilai_stok');
        $obatNames = collect($this->details)
            ->pluck('obat_name')
            ->unique()
            ->values()
            ->implode(', ');

        return "{$totalItems} item obat\n\nObat: {$obatNames}\n\nTotal Nilai Stok: Rp ".number_format($totalNilai, 0, ',', '.');
    }
}
