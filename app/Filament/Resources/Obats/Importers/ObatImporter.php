<?php

namespace App\Filament\Resources\Obats\Importers;

use App\Models\Obat;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ObatImporter extends Importer
{
    protected static ?string $model = Obat::class;

    /**
     * @return array<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('kode_obat')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['kode', 'code', 'kode obat']),
            ImportColumn::make('nama_obat')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['nama', 'name', 'nama obat', 'obat']),
            ImportColumn::make('nama_generik')
                ->rules(['nullable', 'max:255'])
                ->guess(['generik', 'generic']),
            ImportColumn::make('kategori')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['kategori', 'category', 'jenis']),
            ImportColumn::make('satuan')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['satuan', 'unit', 'kemasan']),
            ImportColumn::make('kekuatan')
                ->rules(['nullable', 'max:255'])
                ->guess(['kekuatan', 'strength', 'dosis']),
            ImportColumn::make('bentuk_sediaan')
                ->requiredMapping()
                ->rules(['required'])
                ->guess(['bentuk', 'sediaan', 'form', 'bentuk sediaan']),
            ImportColumn::make('produsen')
                ->rules(['nullable', 'max:255'])
                ->guess(['produsen', 'manufacturer', 'pabrik']),
            ImportColumn::make('kemasan')
                ->rules(['nullable', 'max:255'])
                ->guess(['kemasan', 'packaging']),
            ImportColumn::make('harga_satuan')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->guess(['harga', 'price', 'harga satuan', 'hpp']),
            ImportColumn::make('status')
                ->rules(['required'])
                ->guess(['status']),
            ImportColumn::make('metode_stok')
                ->rules(['nullable', 'max:4'])
                ->guess(['metode', 'metode stok', 'stok method'])
                ->default('fefo'),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import obat selesai berhasil diproses.';

        $failedRows = $import->failed_rows;

        if ($failedRows) {
            $body .= " {$failedRows} baris gagal diimport.";
        }

        return $body;
    }
}
