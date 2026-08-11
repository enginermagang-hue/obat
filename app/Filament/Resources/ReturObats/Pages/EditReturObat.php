<?php

namespace App\Filament\Resources\ReturObats\Pages;

use App\Filament\Resources\ReturObats\ReturObatResource;
use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPenerimaanStok;
use App\Models\Obat;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EditReturObat extends EditRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = ReturObatResource::class;

    public array $details = [];

    public ?string $targetStatus = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->targetStatus = $this->record->status;

        $this->record->loadMissing([
            'distribusi',
            'penerimaan',
        ]);

        $this->details = $this->record->details->map(fn ($detail, int $key) => [
            '_key' => $key,
            'id' => $detail->id,
            'obat_id' => $detail->obat_id,
            'obat_name' => $detail->obat->nama_obat ?? '',
            'batch_id' => $detail->batch_id,
            'batch_number' => $detail->batch?->batch_number ?? '',
            'jumlah_retur' => $detail->jumlah_retur,
            'bukti_foto' => $detail->bukti_foto,
            'catatan' => $detail->catatan,
        ])->values()->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                TextColumn::make('obat_name')
                    ->label('Obat')
                    ->searchable(),
                TextColumn::make('batch_number')
                    ->label('Batch'),
                TextColumn::make('jumlah_retur')
                    ->label('Jumlah Retur')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bukti_foto')
                    ->label('Bukti')
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : 'Ada foto')
                    ->sortable(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Retur')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema(
                        distribusiId: $this->data['distribusi_id'] ?? null,
                        penerimaanId: $this->data['penerimaan_id'] ?? null,
                    ))
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->modalHeading('Edit Item Retur')
                    ->form(fn (): array => $this->getItemFormSchema(
                        distribusiId: $this->data['distribusi_id'] ?? null,
                        penerimaanId: $this->data['penerimaan_id'] ?? null,
                    ))
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Retur')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function getItemFormSchema(
        ?string $distribusiId = null,
        ?string $penerimaanId = null,
    ): array {
        return [
            Select::make('obat_id')
                ->label('Obat')
                ->options(fn (Get $get): array => self::getObatOptions(
                    $get,
                    $distribusiId,
                    $penerimaanId,
                ))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) => $set('batch_id', null)),
            Select::make('batch_id')
                ->label('Batch')
                ->options(fn (Get $get): array => self::getBatchOptions(
                    $get,
                    $distribusiId,
                    $penerimaanId,
                ))
                ->searchable()
                ->nullable()
                ->helperText('Batch stok yang diretur (FEFO)'),
            TextInput::make('jumlah_retur')
                ->label('Jumlah Retur')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(1),
            FileUpload::make('bukti_foto')
                ->label('Foto Bukti')
                ->image()
                ->imagePreviewHeight('80')
                ->disk('public')
                ->directory('retur-bukti')
                ->nullable()
                ->helperText('Upload foto sebagai bukti kondisi obat (opsional)')
                ->columnSpanFull(),
            Textarea::make('catatan')
                ->label('Catatan per Item')
                ->nullable()
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    protected function getItemFormData(array $record): array
    {
        return [
            '_key' => $record['_key'] ?? null,
            'obat_id' => $record['obat_id'] ?? null,
            'batch_id' => $record['batch_id'] ?? null,
            'jumlah_retur' => $record['jumlah_retur'] ?? 1,
            'bukti_foto' => $record['bukti_foto'] ?? null,
            'catatan' => $record['catatan'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addItem(array $data): void
    {
        $obatName = Obat::find($data['obat_id'])?->nama_obat ?? '';
        $batchNumber = BatchStok::find($data['batch_id'])?->batch_number ?? '';

        $this->details[] = [
            '_key' => count($this->details),
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obatName,
            'batch_id' => $data['batch_id'] ?? null,
            'batch_number' => $batchNumber,
            'jumlah_retur' => (int) ($data['jumlah_retur'] ?? 1),
            'bukti_foto' => $data['bukti_foto'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];
        $this->flushCachedTableRecords();
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $data
     */
    protected function editItem(array $record, array $data): void
    {
        $searchKey = $record['_key'] ?? null;
        $key = $searchKey !== null
            ? array_search($searchKey, array_column($this->details, '_key'))
            : false;

        if ($key === false) {
            return;
        }

        $obatName = Obat::find($data['obat_id'])?->nama_obat ?? '';
        $batchNumber = BatchStok::find($data['batch_id'])?->batch_number ?? '';

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obatName,
            'batch_id' => $data['batch_id'] ?? null,
            'batch_number' => $batchNumber,
            'jumlah_retur' => (int) ($data['jumlah_retur'] ?? 1),
            'bukti_foto' => $data['bukti_foto'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];
        $this->flushCachedTableRecords();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function deleteItem(array $record): void
    {
        $searchKey = $record['_key'] ?? null;
        $key = $searchKey !== null
            ? array_search($searchKey, array_column($this->details, '_key'))
            : false;

        if ($key === false) {
            return;
        }

        unset($this->details[$key]);
        $this->details = array_values($this->details);
        $this->flushCachedTableRecords();
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isFaskesUser = filled($user->fasilitasKesehatan);

        return [
            Action::make('simpan')
                ->label('Simpan')
                ->icon('heroicon-m-document-check')
                ->color('gray')
                ->visible(fn (): bool => in_array($this->record?->status, ['draft', 'ditolak'], true))
                ->action(fn () => $this->save()),
            Action::make('selesai')
                ->label('Selesai')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Retur Obat')
                ->modalDescription('Stok akan diproses. Apakah Anda yakin?')
                ->visible(fn (): bool => ! $isFaskesUser
                    && in_array($this->record?->status, ['draft', 'ditolak'], true))
                ->action(fn () => $this->prosesSimpan('selesai')),
            Action::make('ajukan')
                ->label('Ajukan Retur')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => $isFaskesUser
                    && in_array($this->record?->status, ['draft', 'ditolak'], true))
                ->action(fn () => $this->prosesSimpan('menunggu_approval')),
            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record?->status === 'draft')
                ->action(function (): void {
                    $this->record->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
        ];
    }

    protected function prosesSimpan(string $status): void
    {
        $this->targetStatus = $status;
        $this->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->targetStatus) {
            $data['status'] = $this->targetStatus;
        }

        unset($data['details']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $existingIds = collect($this->details)->pluck('id')->filter()->toArray();
        $record->details()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->details as $detail) {
            $detailData = [
                'obat_id' => $detail['obat_id'],
                'batch_id' => $detail['batch_id'] ?? null,
                'jumlah_retur' => $detail['jumlah_retur'],
                'bukti_foto' => $detail['bukti_foto'] ?? null,
                'catatan' => $detail['catatan'] ?? null,
            ];

            if (isset($detail['id'])) {
                $record->details()->where('id', $detail['id'])->update($detailData);
            } else {
                $record->details()->create($detailData);
            }
        }

        // Proses stok jika status berubah ke selesai
        if ($this->targetStatus === 'selesai' && $record->status === 'selesai') {
            app(StokService::class)->prosesReturDiterima(
                $record->fresh('details')
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    private static function getObatOptions(
        Get $get,
        ?string $distribusiId = null,
        ?string $penerimaanId = null,
    ): array {
        // Jika tidak ada distribusi/penerimaan dipilih, return kosong
        if (blank($distribusiId) && blank($penerimaanId)) {
            return [];
        }

        $obatIds = [];

        // Ambil obat_id dari distribusi
        if (filled($distribusiId)) {
            $obatIds = DetailDistribusiObat::where('distribusi_id', $distribusiId)
                ->pluck('obat_id')
                ->unique()
                ->toArray();
        }

        // Ambil obat_id dari penerimaan
        if (filled($penerimaanId)) {
            $obatIds = DetailPenerimaanStok::where('penerimaan_id', $penerimaanId)
                ->pluck('obat_id')
                ->unique()
                ->toArray();
        }

        return Obat::query()
            ->whereIn('id', $obatIds)
            ->where('status', 'aktif')
            ->pluck('nama_obat', 'id')
            ->toArray();
    }

    private static function getBatchOptions(
        Get $get,
        ?string $distribusiId = null,
        ?string $penerimaanId = null,
    ): array {
        $obatId = $get('obat_id');

        if (blank($obatId)) {
            return [];
        }

        $isSuperAdmin = Auth::user()?->hasRole('super_admin');
        $fasilitasId = null;

        if ($isSuperAdmin) {
            $fasilitasId = $get('fasilitas_pengirim_id');
        } else {
            $fasilitasId = Auth::user()?->fasilitas_kesehatan_id;
        }

        $query = BatchStok::query()
            ->where('obat_id', $obatId)
            ->whereIn('status', ['tersedia', 'karantina'])
            ->where('jumlah', '>', 0)
            ->when(
                filled($fasilitasId),
                fn ($q) => $q->where('fasilitas_id', $fasilitasId),
                fn ($q) => $q->whereNull('fasilitas_id'),
            );

        // Filter batch by distribusi details (jika ada)
        if (filled($distribusiId)) {
            $batchIds = DetailDistribusiObat::where('distribusi_id', $distribusiId)
                ->where('obat_id', $obatId)
                ->pluck('batch_id')
                ->filter()
                ->toArray();

            if (filled($batchIds)) {
                $query->whereIn('id', $batchIds);
            }
        }

        // Filter batch by penerimaan details (jika ada)
        if (filled($penerimaanId)) {
            $batchNumbers = DetailPenerimaanStok::where('penerimaan_id', $penerimaanId)
                ->where('obat_id', $obatId)
                ->pluck('batch_number')
                ->toArray();

            if (filled($batchNumbers)) {
                $query->whereIn('batch_number', $batchNumbers);
            }
        }

        // Get obat's metode stok and apply sorting
        $obat = Obat::find($obatId);
        $metode = $obat?->metode_stok->value ?? 'fefo';

        match ($metode) {
            'fifo' => $query->orderBy('tanggal_masuk')->orderBy('id'),
            'lifo' => $query->orderByDesc('tanggal_masuk')->orderByDesc('id'),
            default => $query->orderBy('tanggal_expired')->orderBy('id'), // fefo
        };

        return $query
            ->get()
            ->mapWithKeys(fn (BatchStok $batch): array => [
                $batch->id => sprintf(
                    '%s (Exp: %s, Sisa: %s)',
                    $batch->batch_number,
                    $batch->tanggal_expired->format('d/m/Y'),
                    number_format($batch->jumlah, 0, ',', '.'),
                ),
            ])
            ->toArray();
    }
}
