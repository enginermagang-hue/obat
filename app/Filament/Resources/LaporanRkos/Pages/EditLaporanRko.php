<?php

namespace App\Filament\Resources\LaporanRkos\Pages;

use App\Filament\Resources\LaporanRkos\Concerns\ManagesRkoDetails;
use App\Filament\Resources\LaporanRkos\LaporanRkoResource;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class EditLaporanRko extends EditRecord implements HasSchemas, HasTable
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

    protected function fillForm(): void
    {
        parent::fillForm();

        $this->details = $this->record->details->map(fn ($detail, $index) => [
            '_key' => $index,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat?->nama_obat ?? '',
            'pemakaian_tahun_sebelumnya' => $detail->pemakaian_tahun_sebelumnya,
            'rata_rata_pemakaian_bulanan' => $detail->rata_rata_pemakaian_bulanan,
            'stok_akhir' => $detail->stok_akhir,
            'kebutuhan_tahunan' => $detail->kebutuhan_tahunan,
            'rencana_kebutuhan' => $detail->rencana_kebutuhan,
            'usulan' => $detail->usulan,
            'buffer_stock_persen' => $detail->buffer_stock_persen,
            'buffer_stok_qty' => $detail->buffer_stok_qty,
            'total_kebutuhan' => $detail->total_kebutuhan,
            'harga_perkiraan' => (float) $detail->harga_perkiraan,
            'total_harga' => (float) $detail->total_harga,
            'abc_kategori' => $detail->abc_kategori,
            'keterangan' => $detail->keterangan,
            'ven_kategori_hidden' => $detail->ven_kategori,
            'prediksi_id' => $detail->prediksi_id,
        ])->toArray();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $original = $this->record->getOriginal();

        $data = CreateLaporanRko::prosesRumusRko($data);

        if (($data['status'] ?? $original['status']) === 'diajukan'
            && $original['status'] !== 'diajukan'
            && blank($original['tanggal_pengajuan'])) {
            $data['tanggal_pengajuan'] = now();
        }

        if (in_array($data['status'] ?? $original['status'], ['disetujui', 'ditolak'])
            && ! in_array($original['status'], ['disetujui', 'ditolak'])) {
            $data['tanggal_disetujui'] = now();
            $data['disetujui_oleh'] = $user->id;
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
                    $newDetailIds[] = $detail['id'];
                }
            } else {
                $created = $record->details()->create([
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
                $newDetailIds[] = $created->id;
            }
        }

        $record->details()->whereNotIn('id', $newDetailIds)->delete();

        $totalAnggaran = collect($this->details)->sum('total_harga');
        $record->update(['total_anggaran' => $totalAnggaran]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ajukan')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Ajukan RKO')
                ->modalDescription('Yakin ingin mengajukan RKO ini? Setelah diajukan tidak dapat diedit lagi.')
                ->modalSubmitActionLabel('Ya, Ajukan')
                ->visible(fn () => $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'diajukan',
                        'tanggal_pengajuan' => now(),
                    ]);
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record?->status, ['draft', 'diajukan']))
                ->action(function (): void {
                    $this->record->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
