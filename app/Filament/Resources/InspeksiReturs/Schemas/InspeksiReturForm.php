<?php

namespace App\Filament\Resources\InspeksiReturs\Schemas;

use App\Models\BatchStok;
use App\Models\DetailReturObat;
use App\Models\ReturObat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class InspeksiReturForm
{
    public static function configure(Schema $schema): Schema
    {
        $components = [];

        // Section 1: Informasi Retur
        $components[] = Section::make('Informasi Retur')
            ->description('Data retur obat yang akan diinspeksi')
            ->icon('heroicon-m-document-text')
            ->schema([
                Select::make('retur_id')
                    ->label('Retur Obat')
                    ->options(fn () => ReturObat::query()
                        ->whereIn('status', ['diterima', 'selesai'])
                        ->with('details.obat')
                        ->get()
                        ->mapWithKeys(fn ($retur) => [
                            $retur->id => "{$retur->nomor_retur} - ".($retur->fasilitasPengirim?->nama ?? 'Gudang'),
                        ])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('detail_retur_id', null)),
                Select::make('detail_retur_id')
                    ->label('Item Retur')
                    ->options(fn (Get $get): array => self::getDetailReturOptions($get))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('batch_id', null)),
                Select::make('batch_id')
                    ->label('Batch Stok')
                    ->options(fn (Get $get): array => self::getBatchOptions($get))
                    ->searchable()
                    ->required(),
            ])
            ->columns(3);

        // Section 2: Hasil Inspeksi
        $components[] = Section::make('Hasil Inspeksi')
            ->description('Hasil pemeriksaan obat returan')
            ->icon('heroicon-m-clipboard-document-check')
            ->schema([
                Select::make('hasil_inspeksi')
                    ->label('Hasil Inspeksi')
                    ->options([
                        'layak' => 'Layak',
                        'tidak_layak' => 'Tidak Layak',
                        'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                    ])
                    ->required()
                    ->live(),
                Select::make('tindakan')
                    ->label('Tindakan')
                    ->options([
                        'didistribusi_kembali' => 'Didistribusikan Kembali',
                        'dimusnahkan' => 'Dimusnahkan',
                        'dikembalikan_ke_supplier' => 'Dikembalikan ke Supplier',
                    ])
                    ->required()
                    ->visible(fn (Get $get): bool => $get('hasil_inspeksi') !== 'layak'),
                Textarea::make('catatan_inspeksi')
                    ->label('Catatan Inspeksi')
                    ->nullable()
                    ->rows(3)
                    ->placeholder('Tambahkan catatan hasil inspeksi...'),
            ])
            ->columns(2);

        // Section 3: Informasi Inspektur
        $components[] = Section::make('Informasi Inspektur')
            ->description('Data petugas yang melakukan inspeksi')
            ->icon('heroicon-m-user')
            ->schema([
                DatePicker::make('tanggal_inspeksi')
                    ->label('Tanggal Inspeksi')
                    ->required()
                    ->default(now()),
                Textarea::make('inspected_by_display')
                    ->label('Diperiksa Oleh')
                    ->default(fn () => Auth::user()?->name ?? '')
                    ->disabled()
                    ->dehydrated(false),
            ])
            ->columns(2);

        return $schema->components($components);
    }

    /**
     * Get detail retur options based on retur_id.
     */
    private static function getDetailReturOptions(Get $get): array
    {
        $returId = $get('retur_id');

        if (blank($returId)) {
            return [];
        }

        return DetailReturObat::query()
            ->where('retur_id', $returId)
            ->whereDoesntHave('inspeksi')
            ->with('obat')
            ->get()
            ->mapWithKeys(fn ($detail) => [
                $detail->id => "{$detail->obat->nama_obat} ({$detail->jumlah_retur})",
            ])
            ->toArray();
    }

    /**
     * Get batch options based on detail_retur_id.
     */
    private static function getBatchOptions(Get $get): array
    {
        $detailReturId = $get('detail_retur_id');

        if (blank($detailReturId)) {
            return [];
        }

        $detail = DetailReturObat::find($detailReturId);

        if (! $detail || ! $detail->batch_id) {
            return [];
        }

        $batch = BatchStok::find($detail->batch_id);

        if (! $batch) {
            return [];
        }

        return [
            $batch->id => "{$batch->batch_number} (Exp: {$batch->tanggal_expired->format('d/m/Y')}, Sisa: {$batch->jumlah})",
        ];
    }
}
