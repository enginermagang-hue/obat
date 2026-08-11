<?php

namespace App\Filament\Resources\ReturObats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReturObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_retur')
                    ->label('Nomor Retur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipe_retur')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'puskesmas_ke_gudang' => 'Puskesmas → Gudang',
                        'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
                        'gudang_ke_supplier' => 'Gudang → Supplier',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'puskesmas_ke_gudang' => 'warning',
                        'pustu_ke_puskesmas' => 'info',
                        'gudang_ke_supplier' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('fasilitasPengirim.nama')
                    ->label('Pengirim')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Gudang'),
                TextColumn::make('fasilitasPenerima.nama')
                    ->label('Penerima')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Gudang/Supplier'),
                TextColumn::make('alasan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expired' => 'Kedaluwarsa',
                        'rusak' => 'Rusak',
                        'kelebihan_stok' => 'Kelebihan Stok',
                        'salah_kirim' => 'Salah Kirim',
                        'recall' => 'Recall',
                        'near_expiry' => 'Mendekati Exp',
                        'lainnya' => 'Lainnya',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'expired' => 'danger',
                        'rusak' => 'danger',
                        'near_expiry' => 'warning',
                        'recall' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'menunggu_approval' => 'Menunggu Approval',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dalam_pengiriman' => 'Dalam Pengiriman',
                        'diterima' => 'Diterima',
                        'selesai' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'menunggu_approval' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        'dalam_pengiriman' => 'info',
                        'diterima' => 'success',
                        'selesai' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('tanggal_retur')
                    ->label('Tgl Retur')
                    ->date()
                    ->sortable(),
                TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
