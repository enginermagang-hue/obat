<?php

namespace App\Filament\Resources\ModelPrediksis\Tables;

use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class ModelPrediksisTable
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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => ModelPrediksi::getStatusColor($state))
                    ->sortable(),
                TextColumn::make('akurasi_r2')
                    ->label('Akurasi (R²)')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? number_format((float) $state * 100, 2).'%' : '-')
                    ->sortable(),
                TextColumn::make('tanggal_training')
                    ->label('Tgl. Training')
                    ->date()
                    ->sortable(),
                TextColumn::make('data_training_count')
                    ->label('Data')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fitur_digunakan')
                    ->label('Fitur')
                    ->formatStateUsing(fn (?array $state): string => $state !== null ? implode(', ', $state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('fasilitas_id')
                    ->label('Fasilitas')
                    ->options(fn (): array => FasilitasKesehatan::pluck('nama', 'id')->toArray()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'kadaluarsa' => 'Kadaluarsa',
                        'gagal' => 'Gagal',
                        'data_belum_cukup' => 'Data Belum Cukup',
                    ]),
            ])
            ->recordActions([
                Action::make('train')
                    ->label('Train Model')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->hasPermissionTo('create_model_prediksi'))
                    ->requiresConfirmation()
                    ->modalHeading('Train Model Prediksi')
                    ->modalDescription(fn (ModelPrediksi $record): string => sprintf(
                        'Latih ulang model untuk %s — %s? Model aktif saat ini akan ditandai kadaluarsa.',
                        $record->fasilitas?->nama ?? 'Faskes #'.$record->fasilitas_id,
                        $record->obat?->nama_obat ?? 'Obat #'.$record->obat_id,
                    ))
                    ->action(function (ModelPrediksi $record): void {
                        $exitCode = Artisan::call('ai:train-models', [
                            '--fasilitas-id' => $record->fasilitas_id,
                            '--obat-id' => $record->obat_id,
                            '--force' => true,
                        ]);

                        $output = Artisan::output();

                        if ($exitCode === 0) {
                            Notification::make()
                                ->title('Training berhasil')
                                ->body('Model untuk '.($record->fasilitas?->nama ?? 'Faskes #'.$record->fasilitas_id).' — '.($record->obat?->nama_obat ?? 'Obat #'.$record->obat_id).' berhasil dilatih.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Training gagal')
                                ->body($output)
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
