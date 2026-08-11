<?php

namespace App\Filament\Resources\FasilitasKesehatans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class FasilitasKesehatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_faskes'),
                TextColumn::make('nama'),
                TextColumn::make('tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'puskesmas' ? 'Puskesmas' : 'Pustu')
                    ->color(fn (string $state): string => $state === 'puskesmas' ? 'success' : 'info'),
                TextColumn::make('puskesmasInduk.nama')
                    ->label('Puskesmas Induk'),
                TextColumn::make('pic')
                    ->label('PIC'),
                TextColumn::make('kontak_pic')
                    ->label('Kontak PIC'),
                TextColumn::make('users_count')
                    ->label('Jumlah User')
                    ->counts('users'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'aktif' ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn ($record): string => 'Edit Fasilitas Kesehatan: '.$record->nama)
                    ->modalIcon(Boxicon::PenEditCircle)
                    ->modalWidth(Width::Medium),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
