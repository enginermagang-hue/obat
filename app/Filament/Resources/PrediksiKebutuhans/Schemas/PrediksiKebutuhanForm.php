<?php

namespace App\Filament\Resources\PrediksiKebutuhans\Schemas;

use Carbon\Carbon;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PrediksiKebutuhanForm
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
                        TextEntry::make('periode_bulan')
                            ->label('Bulan')
                            ->formatStateUsing(fn (int $state): string => Carbon::create()->month($state)->translatedFormat('F')),
                        TextEntry::make('periode_tahun')
                            ->label('Tahun'),
                        TextEntry::make('jumlah_prediksi')
                            ->label('Jumlah Prediksi'),
                        TextEntry::make('confidence_lower')
                            ->label('CI Bawah'),
                        TextEntry::make('confidence_upper')
                            ->label('CI Atas'),
                        TextEntry::make('metode')
                            ->label('Metode')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ai_gradient_boost' => 'success',
                                'ai_random_forest' => 'info',
                                'moving_average' => 'warning',
                                'manual' => 'gray',
                                default => 'secondary',
                            }),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->visible(fn ($state): bool => filled($state)),
                    ]),
            ]);
    }
}
