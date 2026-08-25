<?php

namespace App\Filament\Resources\DistribusiObats\Tables;

use App\Models\DistribusiObat;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DistribusiObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_surat_jalan')
                    ->label('No. Surat Jalan')
                    ->copyable()
                    ->copyMessage('Tersalin'),
                TextColumn::make('fasilitasPengirim.nama')
                    ->label('Pengirim')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'puskesmas'),
                TextColumn::make('fasilitasPenerima.nama')
                    ->label('Penerima'),
                TextColumn::make('tanggal_kirim')
                    ->label('Tgl. Kirim')
                    ->date(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'dalam_pengiriman' => 'Dalam Pengiriman',
                        'diterima' => 'Diterima',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'dalam_pengiriman' => 'warning',
                        'diterima' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),
                TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat Detail'),
                    EditAction::make(),
                    Action::make('cetak_faktur')
                        ->label('Cetak Faktur')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->url(fn (DistribusiObat $record): string => route('admin.distribusi.cetak-faktur', ['distribusi' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn (DistribusiObat $record): bool => $record->status !== 'draft'),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Distribusi Obat')
                        ->modalDescription('Apakah Anda yakin ingin menghapus distribusi obat ini? Status permintaan akan dikembalikan ke Disetujui.')
                        ->visible(fn (DistribusiObat $record): bool => $record->status === 'draft')
                        ->after(function (DistribusiObat $record): void {
                            if ($record->permintaan) {
                                $record->permintaan->update(['status' => 'disetujui']);
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
