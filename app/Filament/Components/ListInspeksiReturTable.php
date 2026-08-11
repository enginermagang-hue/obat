<?php

namespace App\Filament\Components;

use App\Models\BatchStok;
use App\Models\DetailReturObat;
use App\Models\InspeksiRetur;
use App\Models\ReturObat;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListInspeksiReturTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(InspeksiRetur::query()
                ->whereHas('retur', fn ($q) => self::applyReturScopeForUser($q))
                ->with(['retur', 'detailRetur.obat', 'inspector']))
            ->columns([
                TextColumn::make('retur.nomor_retur')
                    ->label('Nomor Retur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('detailRetur.obat.nama_obat')
                    ->label('Obat')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hasil_inspeksi')
                    ->label('Hasil')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'layak' => 'Layak',
                        'tidak_layak' => 'Tidak Layak',
                        'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'layak' => 'success',
                        'tidak_layak' => 'danger',
                        'perlu_tindakan_lanjut' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('tindakan')
                    ->label('Tindakan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'didistribusi_kembali' => 'Distribusi Kembali',
                        'dimusnahkan' => 'Dimusnahkan',
                        'dikembalikan_ke_supplier' => 'Kembali ke Supplier',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'didistribusi_kembali' => 'info',
                        'dimusnahkan' => 'danger',
                        'dikembalikan_ke_supplier' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('inspector.name')
                    ->label('Inspektur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tanggal_inspeksi')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hasil_inspeksi')
                    ->label('Hasil Inspeksi')
                    ->options([
                        'layak' => 'Layak',
                        'tidak_layak' => 'Tidak Layak',
                        'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                    ]),
                SelectFilter::make('tindakan')
                    ->label('Tindakan')
                    ->options([
                        'didistribusi_kembali' => 'Distribusi Kembali',
                        'dimusnahkan' => 'Dimusnahkan',
                        'dikembalikan_ke_supplier' => 'Kembali ke Supplier',
                    ]),
            ])
            ->headerActions([
                Action::make('buatInspeksi')
                    ->label('Buat Inspeksi')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn (): bool => auth()->user()?->can('create', InspeksiRetur::class) ?? false)
                    ->modalHeading('Buat Inspeksi Baru')
                    ->modalWidth('lg')
                    ->form(fn (): array => self::getInspeksiFormSchema())
                    ->action(fn (array $data) => $this->createInspeksi($data)),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(fn (InspeksiRetur $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->modalHeading('Edit Inspeksi')
                    ->modalWidth('lg')
                    ->form(fn (InspeksiRetur $record): array => self::getInspeksiFormSchema($record))
                    ->action(fn (array $data, InspeksiRetur $record) => $this->updateInspeksi($data, $record)),
            ]);
    }

    private static function getInspeksiFormSchema(?InspeksiRetur $record = null): array
    {
        return [
            Select::make('retur_id')
                ->label('Retur Obat')
                ->options(fn () => ReturObat::query()
                    ->whereIn('status', ['diterima', 'selesai'])
                    ->where(fn ($q) => self::applyReturScopeForUser($q))
                    ->withCount(['details as total_items'])
                    ->withCount(['details as inspected_items' => fn ($q) => $q->whereHas('inspeksi')])
                    ->get()
                    ->mapWithKeys(fn ($retur) => [
                        $retur->id => "{$retur->nomor_retur} - ".($retur->fasilitasPengirim?->nama ?? 'Gudang')
                            ." ({$retur->inspected_items}/{$retur->total_items})",
                    ])
                    ->toArray())
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->default($record?->retur_id)
                ->afterStateUpdated(fn ($state, callable $set) => $set('detail_retur_id', null)),
            Select::make('detail_retur_id')
                ->label('Item Retur')
                ->options(fn (Get $get): array => self::getDetailReturOptions($get, $record?->id))
                ->searchable()
                ->required()
                ->live()
                ->default($record?->detail_retur_id)
                ->afterStateUpdated(fn ($state, callable $set) => $set('batch_id', null)),
            Select::make('batch_id')
                ->label('Batch Stok')
                ->options(fn (Get $get): array => self::getBatchOptions($get))
                ->searchable()
                ->required()
                ->default($record?->batch_id),
            Select::make('hasil_inspeksi')
                ->label('Hasil Inspeksi')
                ->options([
                    'layak' => 'Layak',
                    'tidak_layak' => 'Tidak Layak',
                    'perlu_tindakan_lanjut' => 'Perlu Tindakan Lanjut',
                ])
                ->required()
                ->live()
                ->default($record?->hasil_inspeksi),
            Select::make('tindakan')
                ->label('Tindakan')
                ->options([
                    'didistribusi_kembali' => 'Didistribusikan Kembali',
                    'dimusnahkan' => 'Dimusnahkan',
                    'dikembalikan_ke_supplier' => 'Dikembalikan ke Supplier',
                ])
                ->required()
                ->default($record?->tindakan)
                ->visible(fn (Get $get): bool => $get('hasil_inspeksi') !== 'layak'),
            Textarea::make('catatan_inspeksi')
                ->label('Catatan Inspeksi')
                ->nullable()
                ->rows(3)
                ->default($record?->catatan_inspeksi)
                ->placeholder('Tambahkan catatan hasil inspeksi...'),
            DatePicker::make('tanggal_inspeksi')
                ->label('Tanggal Inspeksi')
                ->required()
                ->default($record?->tanggal_inspeksi ?? now()),
        ];
    }

    private function createInspeksi(array $data): void
    {
        $duplicate = InspeksiRetur::where('detail_retur_id', $data['detail_retur_id'])
            ->where('retur_id', $data['retur_id'])
            ->exists();

        if ($duplicate) {
            Notification::make()
                ->title('Item ini sudah diinspeksi')
                ->danger()
                ->send();

            return;
        }

        $data['inspected_by'] = Auth::id();

        $inspeksi = InspeksiRetur::create($data);

        self::updateReturStatusIfComplete($inspeksi->retur_id);

        Notification::make()
            ->title('Inspeksi berhasil dibuat')
            ->success()
            ->send();
    }

    private function updateInspeksi(array $data, InspeksiRetur $record): void
    {
        if ($data['detail_retur_id'] !== $record->detail_retur_id) {
            $duplicate = InspeksiRetur::where('detail_retur_id', $data['detail_retur_id'])
                ->where('retur_id', $data['retur_id'])
                ->where('id', '!=', $record->id)
                ->exists();

            if ($duplicate) {
                Notification::make()
                    ->title('Item ini sudah diinspeksi')
                    ->danger()
                    ->send();

                return;
            }
        }

        $record->update($data);

        self::updateReturStatusIfComplete($record->retur_id);

        Notification::make()
            ->title('Inspeksi berhasil diubah')
            ->success()
            ->send();
    }

    private static function updateReturStatusIfComplete(int $returId): void
    {
        $retur = ReturObat::withCount('details')->find($returId);
        if (! $retur) {
            return;
        }

        $inspectedCount = $retur->details()->whereHas('inspeksi')->count();
        if ($inspectedCount >= $retur->details_count) {
            $retur->update(['status' => 'selesai']);
        }
    }

    private static function applyReturScopeForUser($query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('super_admin')) {
            return;
        }

        if ($user->hasRole(['admin_gudang', 'admin_dinas'])) {
            $query->whereIn('tipe_retur', ['puskesmas_ke_gudang', 'gudang_ke_supplier']);

            return;
        }

        if ($user->hasRole('puskesmas') && $user->fasilitas_kesehatan_id) {
            $query->where('tipe_retur', 'pustu_ke_puskesmas')
                ->where('fasilitas_penerima_id', $user->fasilitas_kesehatan_id);

            return;
        }

        $query->whereRaw('0 = 1');
    }

    private static function getDetailReturOptions(Get $get, ?int $editRecordId = null): array
    {
        $returId = $get('retur_id');

        if (blank($returId)) {
            return [];
        }

        return DetailReturObat::query()
            ->where('retur_id', $returId)
            ->where(function ($q) use ($editRecordId) {
                $q->whereDoesntHave('inspeksi');
                if ($editRecordId) {
                    $q->orWhereHas('inspeksi', fn ($iq) => $iq->where('id', $editRecordId));
                }
            })
            ->with('obat')
            ->get()
            ->mapWithKeys(fn ($detail) => [
                $detail->id => "{$detail->obat->nama_obat} ({$detail->jumlah_retur})",
            ])
            ->toArray();
    }

    private static function getBatchOptions(Get $get): array
    {
        $detailReturId = $get('detail_retur_id');

        if (blank($detailReturId)) {
            return [];
        }

        $detail = DetailReturObat::find($detailReturId);

        if (! $detail || ! $detail->batch_id) {
            return [];
        }

        $batch = BatchStok::find($detail->batch_id);

        if (! $batch) {
            return [];
        }

        return [
            $batch->id => "{$batch->batch_number} (Exp: {$batch->tanggal_expired->format('d/m/Y')}, Sisa: {$batch->jumlah})",
        ];
    }

    public function render(): View
    {
        return view('filament.components.inspeksi-retur-table');
    }
}
