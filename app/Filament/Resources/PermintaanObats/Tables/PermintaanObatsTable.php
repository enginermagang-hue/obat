<?php

namespace App\Filament\Resources\PermintaanObats\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stokobat\Boxicons\Boxicon;

class PermintaanObatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('details');
            })
            ->columns([
                TextColumn::make('nomor_permintaan')
                    ->label('Nomor')
                    ->sortable()
                    // ->searchable()
                    ->copyable()
                    ->copyMessage('Tersalin'),
                TextColumn::make('tanggal_permintaan')
                    ->label('Tanggal')
                    ->date('d M, Y')
                    ->sortable(),
                TextColumn::make('fasilitasPengirim.nama')
                    ->label('Pengirim')
                    ->sortable(),
                // ->searchable(),
                TextColumn::make('tipe_permintaan')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
                        'puskesmas_ke_dinas' => 'Puskesmas → Dinas',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pustu_ke_puskesmas' => 'info',
                        'puskesmas_ke_dinas' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                // ->searchable()
                // ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'menunggu_persetujuan' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'sedang_didistribusi' => 'Didistribusi',
                        'diterima' => 'Diterima',
                        'dibatalkan' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'menunggu_persetujuan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        'sedang_didistribusi' => 'info',
                        'diterima' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('tanggal_disetujui')
                    ->label('Tgl. Disetujui')
                    ->date()
                    ->sortable(),
                // ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_diterima')
                    ->label('Tgl. Diterima')
                    ->date()
                    ->sortable(),
                // ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('disetujuiOleh.name')
                    ->label('Disetujui Oleh')
                    // ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon(Boxicon::Eye),
                    EditAction::make()
                        ->color('gray')
                        ->label(function (): string {
                            $user = auth()->user();
                            $user_role = $user->getRoleNames()->first();
                            if ($user_role === 'super_admin') {
                                return 'Proses Permintaan';
                            } else {
                                return 'Edit Permintaan';
                            }
                        })
                        ->icon(function () {
                            $user = auth()->user();
                            $user_role = $user->getRoleNames()->first();
                            if ($user_role === 'super_admin') {
                                return Boxicon::GitPullRequest;
                            } else {
                                return Boxicon::Pencil;
                            }
                        })
                        ->visible(function ($record) {
                            if ($record->status === 'draft') {
                                return true;
                            } elseif ($record->status === 'menunggu_persetujuan') {
                                return true;
                            } else {
                                return false;
                            }
                        }),
                    Action::make('cetak_faktur')
                        ->label('Cetak Faktur')
                        ->icon(Boxicon::Printer)
                        ->color('gray')
                        ->url(fn (Model $record): string => route('admin.permintaan.cetak-faktur', ['permintaan' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn (Model $record): bool => $record->status !== 'draft'),
                ])
                    ->icon(Boxicon::DotsHorizontalRounded),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyState(view('filament.pages.permintaan-obat.table-empty-state'))
            ->defaultSort('tanggal_permintaan', 'desc');
    }
}
