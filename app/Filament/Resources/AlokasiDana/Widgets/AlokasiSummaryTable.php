<?php

namespace App\Filament\Resources\AlokasiDana\Widgets;

use App\Models\SumberDana;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AlokasiSummaryTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?int $tahun = null;

    public ?int $sumber_dana_id = null;

    public ?string $tipe = null;

    protected function getListeners(): array
    {
        return [
            'alokasiDanaFiltersUpdated' => 'updateFilters',
        ];
    }

    public function updateFilters(array $filters): void
    {
        $this->tahun = $filters['tahun'] ?? null;
        $this->sumber_dana_id = $filters['sumber_dana_id'] ?? null;
        $this->tipe = $filters['tipe'] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ringkasan per Sumber Dana')
            ->description('Detail anggaran, realisasi, dan sisa untuk setiap sumber dana')
            ->query(
                SumberDana::query()
                    ->withSum([
                        'sumberDanaPenggunaans as total_realisasi' => function ($query): void {
                            $query->where('tipe', 'realisasi');
                        },
                    ], 'total_biaya')
                    ->withCount([
                        'sumberDanaPenggunaans as jumlah_po' => function ($query): void {
                            $query->where('tipe', 'realisasi');
                        },
                    ])
                    ->orderBy('tahun', 'desc')
                    ->orderBy('kode'),
            )
            ->modifyQueryUsing(function (Builder $query): Builder {
                if ($this->tahun) {
                    $query->where('tahun', $this->tahun);
                }
                if ($this->sumber_dana_id) {
                    $query->where('id', $this->sumber_dana_id);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode Dana')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('nama')
                    ->label('Nama Sumber Dana')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (SumberDana $record): string => $record->nama),
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_anggaran')
                    ->label('Anggaran')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_realisasi')
                    ->label('Realisasi')
                    ->money('IDR', locale: 'id')
                    ->default('Rp 0')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('sisa')
                    ->label('Sisa')
                    ->money('IDR', locale: 'id')
                    ->default('Rp 0')
                    ->alignEnd()
                    ->getStateUsing(function (SumberDana $record): float {
                        $anggaran = (float) $record->total_anggaran;
                        $realisasi = (float) ($record->total_realisasi ?? 0);

                        return $anggaran - $realisasi;
                    })
                    ->color(function (SumberDana $record): string {
                        $sisa = (float) $record->total_anggaran - (float) ($record->total_realisasi ?? 0);

                        return $sisa < 0 ? 'danger' : 'info';
                    }),
                TextColumn::make('persentase')
                    ->label('%')
                    ->alignCenter()
                    ->getStateUsing(function (SumberDana $record): string {
                        $anggaran = (float) $record->total_anggaran;
                        if ($anggaran === 0.0) {
                            return '0%';
                        }
                        $realisasi = (float) ($record->total_realisasi ?? 0);
                        $persen = ($realisasi / $anggaran) * 100;

                        return number_format($persen, 1).'%';
                    })
                    ->color(function (SumberDana $record): string {
                        $anggaran = (float) $record->total_anggaran;
                        if ($anggaran === 0.0) {
                            return 'gray';
                        }
                        $persen = ((float) ($record->total_realisasi ?? 0) / $anggaran) * 100;

                        return match (true) {
                            $persen > 80 => 'danger',
                            $persen > 50 => 'warning',
                            default => 'success',
                        };
                    }),
                TextColumn::make('jumlah_po')
                    ->label('Jumlah PO')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'nonaktif' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                        default => ucfirst($state),
                    })
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('tahun')
                    ->options(fn (): array => array_combine(
                        range(now()->year - 2, now()->year + 1),
                        range(now()->year - 2, now()->year + 1),
                    )),
                SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),
            ])
            ->defaultSort('tahun', 'desc')
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(25)
            ->recordUrl(null);
    }
}
