<?php

namespace App\Filament\Resources\ModelPrediksis\Schemas;

use App\Models\ModelPrediksi;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ModelPrediksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('fasilitas.nama')
                            ->label('Fasilitas'),
                        TextEntry::make('obat.nama_obat')
                            ->label('Obat'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => ModelPrediksi::getStatusColor($state)),
                        TextEntry::make('akurasi_r2')
                            ->label('Akurasi (R²)')
                            ->formatStateUsing(fn (?string $state): string => $state !== null ? number_format((float) $state * 100, 2).'%' : '-'),
                        TextEntry::make('tanggal_training')
                            ->label('Tgl. Training')
                            ->date(),
                        TextEntry::make('data_training_count')
                            ->label('Jumlah Data Training'),
                        TextEntry::make('fitur_digunakan')
                            ->label('Fitur')
                            ->visible(fn ($state): bool => filled($state))
                            ->formatStateUsing(fn (?array $state): string => $state !== null ? implode(', ', $state) : '-'),
                    ]),
            ]);
    }
}
