<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama'),
                TextColumn::make('telepon'),
                TextColumn::make('email'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->icon(Boxicon::EditAlt)
                    ->iconButton()
                    ->modalIcon(Boxicon::EditAlt)
                    ->modalHeading(fn ($record) => 'Edit Supplier: '.$record->nama)
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalFooterActionsAlignment('end'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
