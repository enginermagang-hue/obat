<?php

namespace App\Filament\Resources\LaporanRkos\Pages;

use App\Filament\Resources\LaporanRkos\Concerns\ManagesRkoDetails;
use App\Filament\Resources\LaporanRkos\LaporanRkoResource;
use App\Filament\Resources\LaporanRkos\Schemas\LaporanRkoForm;
use App\Models\PengaturanLaporan;
use App\Services\NomorFormatService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Validation\ValidationException;

class CreateLaporanRko extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable {
        InteractsWithTable::table as protected filamentTable;
    }
    use ManagesRkoDetails {
        ManagesRkoDetails::table insteadof InteractsWithTable;
    }

    protected static string $resource = LaporanRkoResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['dibuat_oleh'] = $user->id;

        // Non-super-admin: lock periode_tahun to admin's configured value
        if (! $user->hasRole('super_admin') && filled($user->fasilitas_kesehatan_id)) {
            $periodeRkoTahun = PengaturanLaporan::get('rko', 'periode_tahun', $user->fasilitas_kesehatan_id);
            if (filled($periodeRkoTahun)) {
                $data['periode_tahun'] = (int) $periodeRkoTahun;
            }
        }

        if (blank($data['nomor_rko'] ?? null)) {
            $data['nomor_rko'] = $this->generateNomorRko($user->fasilitas_kesehatan_id);
        }

        $data['total_anggaran'] = collect($this->details)->sum('total_harga');

        if (($data['status'] ?? 'draft') === 'diajukan') {
            $data['tanggal_pengajuan'] = $data['tanggal_pengajuan'] ?? now();
        }

        if (empty($this->details)) {
            throw ValidationException::withMessages([
                'details' => 'RKO harus memiliki minimal 1 item obat.',
            ]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        foreach ($this->details as $detail) {
            $record->details()->create([
                'obat_id' => $detail['obat_id'],
                'pemakaian_tahun_sebelumnya' => $detail['pemakaian_tahun_sebelumnya'],
                'rata_rata_pemakaian_bulanan' => $detail['rata_rata_pemakaian_bulanan'],
                'stok_akhir' => $detail['stok_akhir'],
                'kebutuhan_tahunan' => $detail['kebutuhan_tahunan'],
                'rencana_kebutuhan' => $detail['rencana_kebutuhan'],
                'usulan' => $detail['usulan'],
                'buffer_stock_persen' => $detail['buffer_stock_persen'],
                'buffer_stok_qty' => $detail['buffer_stok_qty'],
                'total_kebutuhan' => $detail['total_kebutuhan'],
                'ven_kategori' => $detail['ven_kategori_hidden'] ?? null,
                'harga_perkiraan' => $detail['harga_perkiraan'],
                'total_harga' => $detail['total_harga'],
                'abc_kategori' => $detail['abc_kategori'] ?? null,
                'keterangan' => $detail['keterangan'] ?? null,
                'prediksi_id' => $detail['prediksi_id'] ?? null,
            ]);
        }

        $totalItems = count($this->details);
        $totalAnggaran = collect($this->details)->sum('total_harga');

        $record->update(['total_anggaran' => $totalAnggaran]);

        Notification::make()
            ->title('RKO Tersimpan')
            ->body("{$totalItems} item obat. Total anggaran: Rp ".number_format($totalAnggaran, 0, ',', '.'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
            $this->getCreateFormAction()
                ->label('Simpan RKO')
                ->icon('heroicon-m-document-check')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi RKO')
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
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFromPrediksi')
                ->label('Generate dari Prediksi')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('Generate akan mengisi item RKO dari prediksi AI/MA obat. Item yang sudah ada akan ditimpa. Obat tanpa prediksi akan diisi 0.')
                ->action(function (): void {
                    $this->generateFromPrediksi();

                    $total = count($this->details);
                    $withPrediksi = collect($this->details)->where('prediksi_id', '!==', null)->count();

                    Notification::make()
                        ->title('Berhasil di-generate')
                        ->body("{$total} item obat ({$withPrediksi} dari prediksi)")
                        ->success()
                        ->send();
                }),
            Action::make('generateFromPemakaian')
                ->label('Generate dari Pemakaian')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Generate akan mengisi item RKO dari riwayat pemakaian obat tahun sebelumnya. Item yang sudah ada akan ditimpa.')
                ->action(function (): void {
                    $this->generateFromPemakaian();

                    $total = count($this->details);

                    Notification::make()
                        ->title('Berhasil di-generate')
                        ->body("{$total} item obat dari riwayat pemakaian")
                        ->success()
                        ->send();
                }),
        ];
    }

    private function generateNomorRko(?int $fasilitasId = null): string
    {
        return NomorFormatService::generate('laporan_rko', $fasilitasId);
    }

    public static function prosesRumusRko(array $data): array
    {
        $details = $data['details'] ?? [];

        LaporanRkoForm::hitungAbcKategori($details);

        $totalAnggaran = 0;
        foreach ($details as $key => $detail) {
            $usulan = (int) ($detail['usulan'] ?? 0);
            $harga = (float) ($detail['harga_perkiraan'] ?? 0);

            if ($usulan === 0) {
                $totalKebutuhan = (int) ($detail['total_kebutuhan'] ?? 0);
                $details[$key]['usulan'] = $totalKebutuhan;
                $details[$key]['total_harga'] = $totalKebutuhan * $harga;
            }

            $totalAnggaran += (float) ($details[$key]['total_harga'] ?? 0);
        }

        $data['details'] = $details;
        $data['total_anggaran'] = $totalAnggaran;

        return $data;
    }

    protected function getSummaryForConfirm(): string
    {
        $totalItems = count($this->details);
        $totalAnggaran = collect($this->details)->sum('total_harga');
        $obatNames = collect($this->details)
            ->pluck('obat_name')
            ->unique()
            ->values()
            ->implode(', ');

        return "{$totalItems} item obat\n\nObat: {$obatNames}\n\nTotal Anggaran: Rp ".number_format($totalAnggaran, 0, ',', '.');
    }
}
