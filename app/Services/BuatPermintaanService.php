<?php

namespace App\Services;

use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\PermintaanObat;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class BuatPermintaanService
{
    /**
     * Buat draft Permintaan Obat dari baris rekomendasi.
     *
     * @param  Collection<int, array{obat_id:int, rekom:int, satuan:?string, status:string}>  $rows
     */
    public static function buat(int $faskesId, Collection $rows, string $catatan, string $sumber = 'Prediksi AI'): ?PermintaanObat
    {
        if ($rows->isEmpty()) {
            Notification::make()->title('Tidak ada obat yang perlu dipesan untuk filter ini')->info()->send();

            return null;
        }

        $faskes = FasilitasKesehatan::find($faskesId);
        $tipe = $faskes?->tipe === 'pustu' ? 'pustu_ke_puskesmas' : 'puskesmas_ke_dinas';
        $tujuan = $faskes?->tipe === 'pustu' ? $faskes->puskesmas_induk_id : null;

        $permintaan = PermintaanObat::create([
            'fasilitas_pengirim_id' => $faskesId,
            'fasilitas_tujuan_id' => $tujuan,
            'tipe_permintaan' => $tipe,
            'status' => 'draft',
            'tanggal_permintaan' => now(),
            'catatan' => $catatan,
        ]);

        foreach ($rows as $row) {
            $permintaan->details()->create([
                'obat_id' => $row['obat_id'],
                'jumlah_diminta' => $row['rekom'],
                'catatan' => $sumber.': '.$row['rekom'].' '.$row['satuan'].' ('.$row['status'].')',
            ]);
        }

        Notification::make()
            ->title('Permintaan Obat dibuat')
            ->body('Draft permintaan '.$permintaan->nomor_permintaan.' dengan '.$rows->count().' item. Silakan lengkapi & kirim.')
            ->success()
            ->actions([
                Action::make('buka')
                    ->label('Buka Permintaan')
                    ->url(PermintaanObatResource::getUrl('edit', ['record' => $permintaan->id])),
            ])
            ->send();

        return $permintaan;
    }
}
