<?php

namespace App\Filament\Resources\PrediksiKebutuhans\Tables;

use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrediksiKebutuhansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('obat.nama_obat')
                    ->label('Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('periode_bulan')
                    ->label('Bulan')
                    ->formatStateUsing(fn (int $state): string => Carbon::create()->month($state)->translatedFormat('F'))
                    ->sortable(),
                TextColumn::make('periode_tahun')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('jumlah_prediksi')
                    ->label('Prediksi')
                    ->sortable(),
                TextColumn::make('confidence_lower')
                    ->label('CI Bawah')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('confidence_upper')
                    ->label('CI Atas')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metode')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ai_gradient_boost' => 'success',
                        'ai_random_forest' => 'info',
                        'moving_average' => 'warning',
                        'manual' => 'gray',
                        default => 'secondary',
                    })
                    ->description(fn ($record): ?string => $record->metode === 'moving_average' ? 'Data < 6 bulan — fallback' : null)
                    ->sortable(),
                TextColumn::make('model.tanggal_training')
                    ->label('Tgl Training')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('metode')
                    ->label('Metode')
                    ->options([
                        'ai_gradient_boost' => 'Gradient Boost',
                        'ai_random_forest' => 'Random Forest',
                        'moving_average' => 'Moving Average',
                        'manual' => 'Manual',
                    ]),
            ])
            ->recordActions([])
            ->defaultSort('periode_tahun', 'desc')
            ->defaultSort('periode_bulan', 'desc');
    }
}
