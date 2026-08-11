<?php

namespace App\Filament\Resources\SumberDanas\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Stokobat\Boxicons\Boxicon;

class SumberDanasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode'),
                TextColumn::make('nama')
                    ->limit(40),
                TextColumn::make('tahun')
                    ->alignEnd(),
                TextColumn::make('total_anggaran')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->alignEnd(),
                TextColumn::make('keterangan')
                    ->limit(40),
            ])
            ->filters([])
            ->defaultSort('tahun', 'desc')
            ->recordActions([
                EditAction::make()
                    ->modalIcon(Boxicon::PlusCircle)
                    ->modalHeading(fn ($record) => 'Edit Sumber Dana: '.$record->nama)
                    ->modalWidth(Width::Medium)
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitActionLabel('Simpan'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('nonaktifkan')
                        ->label('Nonaktifkan')
                        ->icon(Boxicon::XCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn ($record) => $record->update(['status' => false]));
                        }),
                    BulkAction::make('aktifkan')
                        ->label('Aktifkan')
                        ->icon(Boxicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn ($record) => $record->update(['status' => true]));
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
