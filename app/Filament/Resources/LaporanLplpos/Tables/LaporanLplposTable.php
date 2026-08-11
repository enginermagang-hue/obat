<?php

namespace App\Filament\Resources\LaporanLplpos\Tables;

use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\LaporanLplpos\LaporanLplpoResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stokobat\Boxicons\Boxicon;

class LaporanLplposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_laporan')
                    ->label('No. Laporan')
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Tersalin'),
                TextColumn::make('fasilitas.nama')
                    ->label('Faskes')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->formatStateUsing(fn ($record): string => static::getNamaBulan($record->periode_bulan).' '.$record->periode_tahun)
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('periode_tahun', $direction)->orderBy('periode_bulan', $direction);
                    }),
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
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details')
                    ->sortable(),
                TextColumn::make('tanggal_pembuatan')
                    ->label('Tgl. Pembuatan')
                    ->date()
                    ->sortable(),
                TextColumn::make('dibuatOleh.name')
                    ->label('Petugas')
                    ->placeholder('—'),
            ])
            ->defaultSort('tanggal_pembuatan', 'desc')
            ->actions([
                Action::make('view')
                    ->icon(Boxicon::SolidEye)
                    ->iconButton()
                    ->tooltip('Lihat Detail')
                    ->url(fn ($record) => LaporanLplpoResource::getUrl('show', ['record' => $record])),
                Action::make('cetak')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->tooltip('Cetak PDF')
                    ->url(fn ($record) => CetakPdfPage::getUrl(['type' => 'lplpo', 'id' => $record->id]), shouldOpenInNewTab: true),
                EditAction::make()
                    ->iconButton()
                    ->hidden(fn ($record) => ! auth()->user()?->can('update', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyState(view('filament.pages.lplpo.table-empty-state'));
    }

    private static function getNamaBulan(int $bulan): string
    {
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulanList[$bulan] ?? $bulan;
    }
}
