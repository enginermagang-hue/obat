<?php

namespace App\Filament\Resources\NeracaTahunans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NeracaTahunansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_neraca')
                    ->label('Nomor Neraca'),
                TextColumn::make('fasilitas.nama')
                    ->label('Faskes')
                    ->default('Gudang'),
                TextColumn::make('tahun')
                    ->label('Tahun'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'selesai' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'selesai' => 'Selesai',
                        default => $state,
                    }),
                TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details'),
                TextColumn::make('total_nilai_stok')
                    ->label('Total Nilai Stok')
                    ->money('IDR'),
                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh'),
                TextColumn::make('created_at')
                    ->label('Tanggal Buat')
                    ->date(),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'selesai')
                    ->url(fn ($record) => route('admin.neraca.cetak-pdf', ['neraca' => $record->id]), shouldOpenInNewTab: true),
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'selesai')
                    ->url(fn ($record) => route('admin.neraca.cetak-xls', ['neraca' => $record]), shouldOpenInNewTab: true),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
