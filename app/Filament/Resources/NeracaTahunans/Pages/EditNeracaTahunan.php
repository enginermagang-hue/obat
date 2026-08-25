<?php

namespace App\Filament\Resources\NeracaTahunans\Pages;

use App\Filament\Resources\NeracaTahunans\Concerns\ManagesNeracaDetails;
use App\Filament\Resources\NeracaTahunans\NeracaTahunanResource;
use App\Services\NeracaTahunanService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Validation\ValidationException;
use Stokobat\Boxicons\Boxicon;

class EditNeracaTahunan extends EditRecord implements HasSchemas, HasTable
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

    protected function fillForm(): void
    {
        parent::fillForm();

        $this->details = $this->record->details->map(fn ($detail, $index) => [
            '_key' => $index,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat?->nama_obat ?? '',
            'stok_awal' => $detail->stok_awal,
            'total_masuk' => $detail->total_masuk,
            'total_keluar' => $detail->total_keluar,
            'stok_akhir' => $detail->stok_akhir,
            'stok_optimum' => $detail->stok_optimum,
            'permintaan' => $detail->permintaan,
            'harga_satuan' => (float) $detail->harga_satuan,
            'nilai_stok' => (float) $detail->nilai_stok,
            'keterangan' => $detail->keterangan,
        ])->toArray();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($this->details)) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Tambahkan minimal 1 item obat terlebih dahulu.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'details' => 'Tambahkan minimal 1 item obat terlebih dahulu.',
            ]);
        }

        if ($this->statusToSave !== null) {
            $data['status'] = $this->statusToSave;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $newDetailIds = [];

        foreach ($this->details as $detail) {
            if (isset($detail['id']) && $detail['id'] !== null) {
                $existingDetail = $record->details()->find($detail['id']);
                if ($existingDetail) {
                    $existingDetail->update([
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

                    app(NeracaTahunanService::class)->syncSumberDanaBreakdown($existingDetail);
                    $newDetailIds[] = $detail['id'];
                }
            } else {
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
                $newDetailIds[] = $created->id;
            }
        }

        $record->details()->whereNotIn('id', $newDetailIds)->delete();

        if (empty($this->details)) {
            Notification::make()
                ->title('Neraca Disimpan (Tanpa Detail)')
                ->body('Neraca tersimpan tanpa item obat.')
                ->warning()
                ->send();

            return;
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

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::X),
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon(Boxicon::Save)
                ->color('gray')
                ->disabled(fn (): bool => empty($this->details))
                ->tooltip(fn (): ?string => empty($this->details)
                    ? 'Tambahkan minimal 1 item obat terlebih dahulu'
                    : null),
            Action::make('selesai')
                ->label('Selesai')
                ->icon(Boxicon::Check)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penyelesaian Neraca')
                ->modalDescription(fn (): string => $this->getSummaryForConfirm())
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->action(function (): void {
                    $record = $this->getRecord();

                    $record->update(['status' => 'selesai']);

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->record->status === 'selesai'
            ? static::getResource()::getUrl('view', ['record' => $this->record])
            : static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isFaskesUser = filled($user?->fasilitas_kesehatan_id);
        $isOwner = $isFaskesUser && $this->record?->fasilitas_id === $user->fasilitas_kesehatan_id;

        return [
            Action::make('generate_ulang')
                ->label('Generate Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Generate ulang akan mengisi ulang item neraca dari riwayat stok. Item yang sudah ada akan ditimpa.')
                ->action(function (): void {
                    $this->generateFromStok();
                    $this->flushCachedTableRecords();
                })
                ->visible(fn (): bool => $isOwner && $this->record?->status === 'draft'),

            Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => route('admin.neraca.cetak-pdf', ['neraca' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record?->status === 'selesai'),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('admin.neraca.cetak-xls', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record?->status === 'selesai'),

            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
