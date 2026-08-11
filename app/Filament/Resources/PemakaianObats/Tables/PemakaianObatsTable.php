<?php

namespace App\Filament\Resources\PemakaianObats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PemakaianObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pemakaian')
                    ->label('No. Pemakaian')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—')
                    ->weight('medium'),

                TextColumn::make('tanggal_pemakaian')
                    ->label('Tgl. Pakai')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Gudang')
                    ->visible(fn (): bool => ! Auth::user()?->hasAnyRole(['puskesmas', 'pustu'])),

                TextColumn::make('jenis_pelayanan')
                    ->label('Pelayanan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::getJenisLabel($state))
                    ->color(fn (string $state): string => self::getJenisColor($state))
                    ->sortable(),

                TextColumn::make('nama_pasien')
                    ->label('Pasien')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),

                TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->tooltip('Jumlah item obat yang dipakai'),

                TextColumn::make('user.name')
                    ->label('Petugas')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                // ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->defaultSort('tanggal_pemakaian', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => self::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    private static function canEdit($record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return false;
        }

        return $record->fasilitas_id === $userFaskesId
            && $record->tanggal_pemakaian?->isToday();
    }

    public static function getJenisLabel(string $jenis): string
    {
        return match ($jenis) {
            'rawat_jalan' => 'Rawat Jalan',
            'rawat_inap' => 'Rawat Inap',
            'uks' => 'UKS',
            'posyandu' => 'Posyandu',
            'pusling' => 'Pusling',
            'gigi' => 'Poli Gigi',
            'laboratorium' => 'Laboratorium',
            'apotek' => 'Apotek',
            'lainnya' => 'Lainnya',
            default => $jenis,
        };
    }

    public static function getJenisColor(string $jenis): string
    {
        return match ($jenis) {
            'rawat_jalan' => 'primary',
            'rawat_inap' => 'info',
            'uks' => 'warning',
            'posyandu' => 'success',
            'pusling' => 'warning',
            'gigi' => 'info',
            'laboratorium' => 'gray',
            'apotek' => 'primary',
            'lainnya' => 'gray',
            default => 'gray',
        };
    }
}
