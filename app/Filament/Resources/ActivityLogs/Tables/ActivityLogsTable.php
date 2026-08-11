<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => self::formatLogName($state))
                    ->color(fn (string $state): string => self::getLogNameColor($state)),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => self::getEventColor($state)),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Subjek')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->datetime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([])
            ->paginationMode(PaginationMode::Cursor);
    }

    private static function formatLogName(string $logName): string
    {
        return match ($logName) {
            'auth' => 'Auth',
            'master_data' => 'Master Data',
            'permintaan_obat' => 'Permintaan',
            'distribusi_obat' => 'Distribusi',
            'retur_obat' => 'Retur',
            'penerimaan_stok' => 'Penerimaan',
            'opname_stok' => 'Opname',
            'laporan_lplpo' => 'LPLPO',
            'laporan_rko' => 'RKO',
            'laporan_neraca' => 'Neraca',
            'user_management' => 'User Mgmt',
            default => str($logName)->headline()->toString(),
        };
    }

    private static function getLogNameColor(string $logName): string
    {
        return match ($logName) {
            'auth' => 'warning',
            'master_data' => 'gray',
            'permintaan_obat', 'distribusi_obat' => 'info',
            'retur_obat', 'penerimaan_stok' => 'success',
            'opname_stok' => 'danger',
            'user_management' => 'primary',
            default => 'gray',
        };
    }

    private static function getEventColor(?string $event): string
    {
        return match ($event) {
            'login' => 'success',
            'logout' => 'warning',
            'failed_login' => 'danger',
            'created' => 'success',
            'updated', 'role_updated' => 'info',
            'deleted' => 'danger',
            'restored' => 'warning',
            'approved', 'completed', 'received' => 'success',
            'rejected' => 'danger',
            'generated' => 'primary',
            default => 'gray',
        };
    }
}
