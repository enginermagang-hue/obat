<?php

namespace App\Filament\Resources\StokFaskes\Tables;

use App\Filament\Resources\StokFaskes\Pages\DaftarBatchStokFaskes;
use App\Filament\Resources\StokFaskes\Pages\RiwayatStokFaskes;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StokFaskesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fasilitas.kode_faskes')
                    ->label('Kode Faskes')
                    ->sortable()
                    ->searchable()
                    ->hidden(fn (): bool => filled(Auth::user()?->fasilitas_kesehatan_id)),
                TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas Kesehatan')
                    ->sortable()
                    ->searchable()
                    ->hidden(fn (): bool => filled(Auth::user()?->fasilitas_kesehatan_id)),
                TextColumn::make('fasilitas.tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'puskesmas' => 'Puskesmas',
                        'pustu' => 'Pustu',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'puskesmas' => 'primary',
                        'pustu' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->hidden(fn (): bool => filled(Auth::user()?->fasilitas_kesehatan_id)),
                TextColumn::make('obat.kode_obat')
                    ->label('Kode Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('obat.nama_obat')
                    ->label('Nama Obat')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('obat.kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('obat.satuan')
                    ->label('Satuan')
                    ->sortable(),
                TextColumn::make('jumlah')
                    ->label('Jumlah Stok')
                    ->alignEnd()
                    ->sortable()
                    ->color(fn ($record): string => self::getStokColor($record))
                    ->weight(fn ($record): string => self::isStokMenipis($record) ? 'bold' : 'normal'),
                TextColumn::make('stok_minimum')
                    ->label('Stok Minimum')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status_stok')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record): string => self::getStatusLabel($record))
                    ->color(fn ($record): string => self::getStatusColor($record)),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('obat.nama_obat')
            ->recordUrl(fn ($record): ?string => DaftarBatchStokFaskes::getUrl([
                'obat_id' => $record->obat_id,
                'fasilitas_id' => $record->fasilitas_id,
            ]))
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('lihat_riwayat')
                    ->label('Riwayat Stok')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn ($record) => RiwayatStokFaskes::getUrl([
                        'obat_id' => $record->obat_id,
                        'fasilitas_id' => $record->fasilitas_id,
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }

    private static function isStokMenipis($record): bool
    {
        return $record->stok_minimum > 0 && $record->jumlah <= $record->stok_minimum;
    }

    private static function getStokColor($record): string
    {
        if ($record->jumlah === 0) {
            return 'danger';
        }

        if (self::isStokMenipis($record)) {
            return 'warning';
        }

        return 'success';
    }

    private static function getStatusLabel($record): string
    {
        if ($record->jumlah === 0) {
            return 'Habis';
        }

        if (self::isStokMenipis($record)) {
            return 'Menipis';
        }

        return 'Tersedia';
    }

    private static function getStatusColor($record): string
    {
        if ($record->jumlah === 0) {
            return 'danger';
        }

        if (self::isStokMenipis($record)) {
            return 'warning';
        }

        return 'success';
    }
}
