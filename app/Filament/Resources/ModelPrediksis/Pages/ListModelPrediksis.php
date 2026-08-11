<?php

namespace App\Filament\Resources\ModelPrediksis\Pages;

use App\Filament\Resources\ModelPrediksis\ModelPrediksiResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListModelPrediksis extends ListRecords
{
    protected static string $resource = ModelPrediksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('train_all')
                ->label('Train All Models')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Train Semua Model')
                ->modalDescription('Latih ulang semua model prediksi? Proses ini akan memproses seluruh kombinasi faskes + obat yang memiliki data pemakaian. Model aktif akan ditandai kadaluarsa jika training berhasil.')
                ->modalSubmitActionLabel('Mulai Training')
                ->action(function (): void {
                    $exitCode = Artisan::call('ai:train-models', ['--force' => true]);

                    $output = Artisan::output();

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Training semua model berhasil')
                            ->body('Semua kombinasi faskes + obat berhasil dilatih.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Training gagal')
                            ->body($output)
                            ->danger()
                            ->send();
                    }

                    $this->dispatch('$refresh');
                })
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('create_model_prediksi')),
        ];
    }
}
