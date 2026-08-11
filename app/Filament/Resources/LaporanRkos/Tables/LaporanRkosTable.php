<?php

namespace App\Filament\Resources\LaporanRkos\Tables;

use App\Filament\Pages\CetakPdfPage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LaporanRkosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_rko')
                    ->label('Nomor RKO'),
                TextColumn::make('fasilitas.nama')
                    ->label('Faskes'),
                TextColumn::make('periode_tahun')
                    ->label('Tahun'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    }),
                TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details'),
                TextColumn::make('total_anggaran')
                    ->label('Total Anggaran')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_pembuatan')
                    ->label('Tanggal Buat')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Diajukan')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_disetujui')
                    ->label('Tanggal Disetujui')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('disetujuiOleh.name')
                    ->label('Disetujui Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions(self::getRecordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()
                    ->hidden(function ($record): bool {
                        $user = auth()->user();

                        if ($user?->hasRole('admin_gudang')) {
                            return true;
                        }

                        if ($user?->hasRole('super_admin') || $user?->hasRole('admin_dinas')) {
                            return false;
                        }

                        return filled($user?->fasilitas_kesehatan_id) && $record->fasilitas_id !== $user->fasilitas_kesehatan_id;
                    }),
                ViewAction::make(),
                Action::make('ajukan')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan RKO')
                    ->modalDescription('Yakin ingin mengajukan RKO ini? Setelah diajukan tidak dapat diedit lagi.')
                    ->modalSubmitActionLabel('Ya, Ajukan')
                    ->visible(fn ($record) => $record->status === 'draft' && $record->fasilitas_id === auth()->user()?->fasilitas_kesehatan_id)
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'diajukan',
                            'tanggal_pengajuan' => now(),
                        ]);
                        Notification::make()
                            ->title('RKO Diajukan')
                            ->success()
                            ->send();
                    }),
                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'disetujui')
                    ->url(fn ($record) => CetakPdfPage::getUrl(['type' => 'rko', 'id' => $record->id]), shouldOpenInNewTab: true),
                Action::make('cetak_xls')
                    ->label('Export XLS')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'disetujui')
                    ->url(fn ($record) => route('admin.rko.cetak-xls', ['rko' => $record]), shouldOpenInNewTab: true),
            ]),
        ];
    }
}
