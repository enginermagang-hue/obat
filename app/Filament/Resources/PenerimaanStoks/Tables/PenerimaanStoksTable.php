<?php

namespace App\Filament\Resources\PenerimaanStoks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class PenerimaanStoksTable
{
    public static function configure(Table $table, string $activeTab = 'dinas'): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_penerimaan')
                    ->label('No. Penerimaan')
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Tersalin'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pembelian' => 'Pembelian',
                        'hibah' => 'Hibah',
                        'stok_awal' => 'Stok Awal',
                        'penyesuaian' => 'Penyesuaian',
                        'distribusi' => 'Distribusi',
                        'manual' => 'Manual',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pembelian' => 'info',
                        'hibah' => 'success',
                        'stok_awal' => 'warning',
                        'penyesuaian' => 'gray',
                        'distribusi' => 'primary',
                        'manual' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'dikonfirmasi' => 'Dikonfirmasi',
                        'dibatalkan' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'dikonfirmasi' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('tanggal_penerimaan')
                    ->label('Tgl. Penerimaan')
                    ->date()
                    ->sortable(),
                TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas')
                    ->placeholder('Gudang')
                    ->sortable(),
                TextColumn::make('supplier.nama')
                    ->label('Supplier')
                    ->sortable()
                    ->placeholder('—')
                    ->visible($activeTab !== 'faskes'),
                TextColumn::make('sumberDana.kode')
                    ->label('Sumber Dana')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('total_biaya')
                    ->money('IDR')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('nomor_po')
                    ->label('No. PO')
                    ->placeholder('—'),
                TextColumn::make('nomor_invoice')
                    ->label('No. Invoice')
                    ->placeholder('—'),
                TextColumn::make('distribusi.nomor_surat_jalan')
                    ->label('No. Surat Jalan')
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->placeholder('—'),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->placeholder('—'),
            ])
            ->defaultSort('tanggal_penerimaan', 'desc')
            ->actions([
                ViewAction::make()
                    ->icon(Boxicon::SolidEye)
                    ->iconButton()
                    ->tooltip('Lihat Detail'),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyState(view('filament.pages.penerimaan-stok.table-empty-state'));
    }
}
