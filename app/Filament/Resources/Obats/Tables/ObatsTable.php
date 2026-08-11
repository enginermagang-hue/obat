<?php

namespace App\Filament\Resources\Obats\Tables;

use App\Enums\MetodeStok;
use App\Models\Obat;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class ObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_obat')
                    ->label('Obat')
                    ->view('filament.tables.columns.obat-nama-column'),
                TextColumn::make('kekuatan'),
                TextColumn::make('produsen'),
                TextColumn::make('ven_kategori')
                    ->label('VEN')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'V' => 'danger',
                        'E' => 'warning',
                        'N' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'V' => 'Vital',
                        'E' => 'Esensial',
                        'N' => 'Non-Esensial',
                        default => '-',
                    })
                    ->alignCenter(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'aktif' ? 'success' : 'danger')
                    ->alignCenter(),
                TextColumn::make('metode_stok')
                    ->label('Metode Stok')
                    ->badge()
                    ->color(fn (MetodeStok $state): string => match ($state) {
                        MetodeStok::FEFO => 'info',
                        MetodeStok::FIFO => 'warning',
                        MetodeStok::LIFO => 'success',
                    })
                    ->formatStateUsing(fn (MetodeStok $state): string => $state->getLabel())
                    ->alignCenter(),
                TextColumn::make('harga_satuan')
                    ->money('IDR'),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon(Boxicon::Edit)
                        ->modalIcon(Boxicon::Edit)
                        ->modalHeading(fn (Obat $record) => "Edit Obat: {$record->nama_obat}")
                        ->modalWidth(Width::ExtraLarge)
                        ->modalFooterActionsAlignment(Alignment::End),
                    DeleteAction::make()
                        ->icon(Boxicon::Trash),
                ])
                    ->icon(Boxicon::DotsHorizontalRounded),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginationMode(PaginationMode::Cursor);
    }
}
