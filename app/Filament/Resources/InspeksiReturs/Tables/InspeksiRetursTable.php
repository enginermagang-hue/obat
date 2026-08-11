<?php

namespace App\Filament\Resources\InspeksiReturs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InspeksiRetursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('retur.nomor_retur')
                    ->label('Nomor Retur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('detailRetur.obat.nama_obat')
                    ->label('Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hasil_inspeksi')
                    ->label('Hasil')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'layak' => 'Layak',
                        'tidak_layak' => 'Tidak Layak',
                        'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'layak' => 'success',
                        'tidak_layak' => 'danger',
                        'perlu_tindakan_lanjut' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('tindakan')
                    ->label('Tindakan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'didistribusi_kembali' => 'Distribusi Kembali',
                        'dimusnahkan' => 'Dimusnahkan',
                        'dikembalikan_ke_supplier' => 'Kembali ke Supplier',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'didistribusi_kembali' => 'info',
                        'dimusnahkan' => 'danger',
                        'dikembalikan_ke_supplier' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('inspector.name')
                    ->label('Inspektur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tanggal_inspeksi')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hasil_inspeksi')
                    ->label('Hasil Inspeksi')
                    ->options([
                        'layak' => 'Layak',
                        'tidak_layak' => 'Tidak Layak',
                        'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                    ]),
                SelectFilter::make('tindakan')
                    ->label('Tindakan')
                    ->options([
                        'didistribusi_kembali' => 'Distribusi Kembali',
                        'dimusnahkan' => 'Dimusnahkan',
                        'dikembalikan_ke_supplier' => 'Kembali ke Supplier',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
