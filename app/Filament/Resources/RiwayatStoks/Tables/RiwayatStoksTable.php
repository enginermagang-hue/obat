<?php

namespace App\Filament\Resources\RiwayatStoks\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiwayatStoksTable
{
    public static function configure(Table $table, array $hideColumns = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                TextColumn::make('obat.kode_obat')
                    ->label('Kode Obat')
                    ->searchable()
                    ->hidden(in_array('obat.kode_obat', $hideColumns, true)),
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->searchable()
                    ->hidden(in_array('obat.nama_obat', $hideColumns, true)),
                TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas')
                    ->searchable()
                    ->toggleable()
                    ->visible(fn (): bool => ! in_array('fasilitas.nama', $hideColumns, true)
                        && ! auth()->user()->hasAnyRole(['puskesmas', 'pustu'])),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => self::getTipeLabel($state))
                    ->color(fn (string $state): string => self::getTipeColor($state)),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->alignRight()
                    ->numeric()
                    ->color(fn ($record): string => $record->tipe === 'keluar' || $record->tipe === 'distribusi_keluar' || $record->tipe === 'rusak' || $record->tipe === 'hilang' || $record->tipe === 'expired' ? 'danger' : 'success'),
                TextColumn::make('stok_sebelum')
                    ->label('Stok Sebelum')
                    ->alignRight()
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('stok_sesudah')
                    ->label('Stok Sesudah')
                    ->alignRight()
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
                TextColumn::make('referensi_type')
                    ->label('Dokumen')
                    ->formatStateUsing(fn ($record): string => self::getReferensiLabel($record))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([])
            ->recordActions([]);
    }

    public static function getTipeLabel(string $tipe): string
    {
        return match ($tipe) {
            'masuk' => 'Masuk',
            'keluar' => 'Keluar',
            'distribusi_masuk' => 'Dist. Masuk',
            'distribusi_keluar' => 'Dist. Keluar',
            'rusak' => 'Rusak',
            'hilang' => 'Hilang',
            'expired' => 'Expired',
            'opname' => 'Opname',
            'penyesuaian' => 'Penyesuaian',
            default => $tipe,
        };
    }

    public static function getTipeColor(string $tipe): string
    {
        return match ($tipe) {
            'masuk' => 'success',
            'keluar' => 'danger',
            'distribusi_masuk' => 'info',
            'distribusi_keluar' => 'warning',
            'rusak', 'hilang' => 'danger',
            'expired' => 'warning',
            'opname' => 'gray',
            'penyesuaian' => 'primary',
            default => 'gray',
        };
    }

    private static function getReferensiLabel($record): string
    {
        if ($record->referensi_type === null) {
            return '-';
        }

        $class = class_basename($record->referensi_type);

        return "{$class} #{$record->referensi_id}";
    }
}
