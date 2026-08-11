<?php

namespace App\Filament\Resources\LaporanLplpos\Pages;

use App\Filament\Resources\LaporanLplpos\Concerns\ManagesLplpoDetails;
use App\Filament\Resources\LaporanLplpos\LaporanLplpoResource;
use App\Services\LaporanLplpoService;
use App\Services\NomorFormatService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Stokobat\Boxicons\Boxicon;

class CreateLaporanLplpo extends CreateRecord implements HasSchemas, HasTable
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['dibuat_oleh'] = $user->id;

        $fasilitasId = $data['fasilitas_id'] ?? $user->fasilitas_kesehatan_id;

        if (blank($data['nomor_laporan'] ?? null)) {
            $data['nomor_laporan'] = $this->generateNomorLaporan($fasilitasId);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (filled($this->details)) {
            foreach ($this->details as $detail) {
                $record->details()->create([
                    'obat_id' => $detail['obat_id'],
                    'stok_awal' => $detail['stok_awal'],
                    'jumlah_masuk' => $detail['jumlah_masuk'],
                    'jumlah_keluar' => $detail['jumlah_keluar'],
                    'sisa_stok' => $detail['sisa_stok'],
                    'stok_optimum' => $detail['stok_optimum'],
                    'permintaan_selanjutnya' => $detail['permintaan_selanjutnya'],
                    'keterangan' => $detail['keterangan'] ?? null,
                ]);
            }
        } else {
            try {
                app(LaporanLplpoService::class)->generate($record);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    protected function getFormActions(): array
    {
        $actions = [
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::SolidX)
                ->color('gray'),
            $this->getCreateFormAction()
                ->label('Simpan')
                ->icon(Boxicon::SolidSave)
                ->color('primary'),
        ];

        return array_reverse($actions);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFromRiwayat')
                ->label('Generate dari Riwayat')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Generate akan mengisi item obat dari riwayat stok periode ini. Item yang sudah ada akan ditimpa.')
                ->action(function (): void {
                    $this->generateFromRiwayat();

                    $total = count($this->details);

                    Notification::make()
                        ->title('Berhasil di-generate')
                        ->body("{$total} item obat dari riwayat stok")
                        ->success()
                        ->send();
                }),
        ];
    }

    private function generateNomorLaporan(?int $fasilitasId = null): string
    {
        return NomorFormatService::generate('laporan_lplpo', $fasilitasId);
    }
}
