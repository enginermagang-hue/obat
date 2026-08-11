<?php

namespace App\Filament\Resources\StokGudangs\Tables;

use App\Filament\Resources\StokGudangs\Pages\RiwayatStokObat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StokGudangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obat.kode_obat')
                    ->label('Kode Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('obat.kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('obat.satuan')
                    ->label('Satuan')
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah Stok')
                    ->sortable()
                    ->color(fn ($record): string => self::getStokColor($record))
                    ->weight(fn ($record): string => self::isStokMenipis($record) ? 'bold' : 'normal'),
                TextColumn::make('stok_minimum')
                    ->label('Stok Minimum')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status_stok')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record): string => self::getStatusLabel($record))
                    ->color(fn ($record): string => self::getStatusColor($record)),
                TextColumn::make('obat.bentuk_sediaan')
                    ->label('Sediaan')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tablet' => 'Tablet',
                        'kapsul' => 'Kapsul',
                        'sirup' => 'Sirup',
                        'salep' => 'Salep',
                        'injeksi' => 'Injeksi',
                        'drop' => 'Drop',
                        'inhaler' => 'Inhaler',
                        'suppositoria' => 'Suppositoria',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('obat.nama_obat')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record): ?string => RiwayatStokObat::getUrl(['obat_id' => $record->obat_id]))
            ->openRecordUrlInNewTab(false)
            ->recordActions([]);
    }

    private static function isStokMenipis($record): bool
    {
        return $record->stok_minimum > 0 && $record->jumlah <= $record->stok_minimum;
    }

    private static function getStokColor($record): string
    {
        if ($record->jumlah === 0) {
            return 'danger';
        }

        if (self::isStokMenipis($record)) {
            return 'warning';
        }

        return 'success';
    }

    private static function getStatusLabel($record): string
    {
        if ($record->jumlah === 0) {
            return 'Habis';
        }

        if (self::isStokMenipis($record)) {
            return 'Menipis';
        }

        return 'Tersedia';
    }

    private static function getStatusColor($record): string
    {
        if ($record->jumlah === 0) {
            return 'danger';
        }

        if (self::isStokMenipis($record)) {
            return 'warning';
        }

        return 'success';
    }
}
