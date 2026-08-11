<?php

namespace App\Filament\Resources\DashboardAi\Widgets;

use App\Models\PrediksiKebutuhan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class CriticalPredictionAlerts extends TableWidget
{
    protected static ?string $heading = '⚠ Peringatan Stok Kritis';

    public ?int $fasilitas_id = null;

    public ?int $obat_id = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    protected function getListeners(): array
    {
        return [
            'dashboardFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->fasilitas_id = $filters['fasilitas_id'] ?? null;
        $this->obat_id = $filters['obat_id'] ?? null;
        $this->bulan = $filters['bulan'] ?? now()->month;
        $this->tahun = $filters['tahun'] ?? now()->year;
    }

    public function table(Table $table): Table
    {
        $query = PrediksiKebutuhan::query()
            ->select([
                'prediksi_kebutuhan.*',
                DB::raw('COALESCE(sf.jumlah, 0) as stok_saat_ini'),
                DB::raw('(prediksi_kebutuhan.jumlah_prediksi - COALESCE(sf.jumlah, 0)) as kekurangan'),
            ])
            ->leftJoin('stok_faskes as sf', function ($join) {
                $join->on('prediksi_kebutuhan.fasilitas_id', '=', 'sf.fasilitas_id')
                    ->on('prediksi_kebutuhan.obat_id', '=', 'sf.obat_id');
            })
            ->where('prediksi_kebutuhan.periode_bulan', $this->bulan)
            ->where('prediksi_kebutuhan.periode_tahun', $this->tahun)
            ->whereRaw('COALESCE(sf.jumlah, 0) < prediksi_kebutuhan.jumlah_prediksi')
            ->orderByDesc('kekurangan')
            ->limit(10);

        if ($this->fasilitas_id) {
            $query->where('prediksi_kebutuhan.fasilitas_id', $this->fasilitas_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('fasilitas.nama')
                    ->label('Fasilitas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('obat.nama_obat')
                    ->label('Obat')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_prediksi')
                    ->label('Kebutuhan (Prediksi)')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('stok_saat_ini')
                    ->label('Stok Saat Ini')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state < 10 ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('kekurangan')
                    ->label('Kekurangan')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 100 ? 'danger' : ($state > 50 ? 'warning' : 'info')),

                Tables\Columns\TextColumn::make('confidence_lower')
                    ->label('CI Bawah')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('confidence_upper')
                    ->label('CI Atas')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('metode')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ai_gradient_boost' => 'Gradient Boost',
                        'ai_random_forest' => 'Random Forest',
                        'moving_average' => 'Moving Average',
                        'manual' => 'Manual',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ai_gradient_boost' => 'success',
                        'ai_random_forest' => 'info',
                        'moving_average' => 'warning',
                        'manual' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->paginated([5, 10, 25]);
    }
}
