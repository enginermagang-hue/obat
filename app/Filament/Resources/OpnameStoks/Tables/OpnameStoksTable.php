<?php

namespace App\Filament\Resources\OpnameStoks\Tables;

use App\Models\DetailOpnameStok;
use App\Models\OpnameStok;
use App\Services\StokService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpnameStoksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_opname')
                    ->label('Nomor Opname'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'penyesuaian' => 'warning',
                        'stok_awal' => 'success',
                        'stok_baru' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'penyesuaian' => 'Penyesuaian',
                        'stok_awal' => 'Stok Awal',
                        'stok_baru' => 'Stok Baru',
                        default => $state,
                    }),
                TextColumn::make('fasilitas.nama')
                    ->label('Faskes')
                    ->placeholder('Gudang'),
                TextColumn::make('tanggal_opname')
                    ->label('Tanggal Opname')
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'selesai' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'selesai' => 'Selesai',
                        default => $state,
                    }),
                TextColumn::make('details_count')
                    ->label('Jumlah Item')
                    ->counts('details'),
                TextColumn::make('user.name')
                    ->label('Petugas'),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),
                EditAction::make()
                    ->modalWidth(Width::ExtraLarge)
                    ->visible(fn (OpnameStok $record): bool => $record->status !== 'selesai')
                    ->mutateRecordDataUsing(function (array $data, OpnameStok $record): array {
                        $data['items'] = $record->details->map(fn (DetailOpnameStok $detail): array => [
                            'id' => $detail->id,
                            'obat_id' => $detail->obat_id,
                            'batch_id' => $detail->batch_id,
                            'stok_sistem' => $detail->stok_sistem,
                            'stok_fisik' => $detail->stok_fisik,
                            'selisih' => $detail->selisih,
                            'batch_number' => $detail->batch_number,
                            'tanggal_expired' => $detail->tanggal_expired?->format('Y-m-d'),
                        ])->toArray();

                        return $data;
                    })
                    ->mutateDataUsing(function (array $data, OpnameStok $record): array {
                        $tipe = $data['tipe'] ?? 'penyesuaian';
                        foreach ($data['items'] ?? [] as &$item) {
                            $item['selisih'] = match ($tipe) {
                                'stok_awal', 'stok_baru' => $item['stok_fisik'] ?? 0,
                                default => ($item['stok_fisik'] ?? 0) - ($item['stok_sistem'] ?? 0),
                            };
                        }

                        session()->flash('_opname_prev_status', $record->getOriginal('status'));
                        session()->flash('_opname_prev_details', $record->details()->get());

                        return $data;
                    })
                    ->after(function (OpnameStok $record, array $data): void {
                        $previousStatus = session()->get('_opname_prev_status');
                        $previousDetails = session()->get('_opname_prev_details');

                        $items = $data['items'] ?? [];

                        $existingIds = collect($items)->pluck('id')->filter()->toArray();
                        $record->details()->whereNotIn('id', $existingIds)->delete();

                        foreach ($items as $item) {
                            $detailData = [
                                'obat_id' => $item['obat_id'],
                                'batch_id' => $item['batch_id'] ?? null,
                                'stok_sistem' => $item['stok_sistem'] ?? 0,
                                'stok_fisik' => $item['stok_fisik'] ?? 0,
                                'selisih' => $item['selisih'] ?? 0,
                                'batch_number' => $item['batch_number'] ?? null,
                                'tanggal_expired' => $item['tanggal_expired'] ?? null,
                                'keterangan' => null,
                            ];

                            if (isset($item['id'])) {
                                $record->details()->where('id', $item['id'])->update($detailData);
                            } else {
                                $record->details()->create($detailData);
                            }
                        }

                        if ($previousStatus === 'selesai') {
                            app(StokService::class)->reverseOpname($record, $previousDetails);
                        }

                        if ($record->status === 'selesai') {
                            app(StokService::class)->prosesOpnameSelesai($record);
                        }

                        session()->forget(['_opname_prev_status', '_opname_prev_details']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
