<?php

namespace App\Filament\Resources\ReturObats\Pages;

use App\Filament\Resources\ReturObats\ReturObatResource;
use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPenerimaanStok;
use App\Models\DistribusiObat;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\ReturObat;
use App\Services\StokService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Stokobat\Boxicons\Boxicon;

class CreateReturObat extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = ReturObatResource::class;

    public array $details = [];

    public ?string $targetStatus = 'draft';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                TextColumn::make('obat_name')
                    ->label('Obat'),
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

        $newItem = [
            '_key' => count($this->details),
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obatName,
            'batch_id' => $data['batch_id'] ?? null,
            'batch_number' => $batchNumber,
            'jumlah_retur' => (int) ($data['jumlah_retur'] ?? 1),
            'bukti_foto' => $data['bukti_foto'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];

        $this->details[] = $newItem;
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
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('draft')),
            Action::make('selesai')
                ->label('Selesai')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Retur Obat')
                ->modalDescription('Stok akan diproses. Apakah Anda yakin?')
                ->visible(! $isFaskesUser)
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('selesai')),
            Action::make('ajukan')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible($isFaskesUser)
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('menunggu_approval')),
        ];
    }

    protected function getFormActions(): array
    {
        $user = auth()->user();
        $isFaskesUser = filled($user->fasilitasKesehatan);

        return [
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::X)
                ->iconPosition(IconPosition::After)
                ->color('danger'),
            Action::make('simpan')
                ->label('Simpan')
                ->icon(Boxicon::Save)
                ->iconPosition(IconPosition::After)
                ->color('gray')
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('draft'))
                ->extraAttributes([
                    'class' => 'ml-auto',
                ]),
            Action::make('selesai')
                ->label('Selesai')
                ->icon(Boxicon::CheckCircle)
                ->iconPosition(IconPosition::After)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Retur Obat')
                ->modalDescription('Stok akan diproses. Apakah Anda yakin?')
                ->visible(! $isFaskesUser)
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('selesai')),
            Action::make('ajukan')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible($isFaskesUser)
                ->disabled(fn (): bool => $this->isHeaderIncomplete())
                ->tooltip(fn (): ?string => $this->getDisabledTooltip())
                ->action(fn () => $this->prosesSimpan('menunggu_approval')),
        ];
    }

    /**
     * Cek apakah header form sudah lengkap dan daftar item tidak kosong.
     * Field wajib: alasan, tanggal_retur, dan minimal 1 item.
     */
    protected function isHeaderIncomplete(): bool
    {
        $alasan = $this->data['alasan'] ?? null;
        $tanggal = $this->data['tanggal_retur'] ?? null;

        return blank($alasan)
            || blank($tanggal)
            || empty($this->details);
    }

    /**
     * Pesan tooltip yang menjelaskan kenapa tombol disabled.
     */
    protected function getDisabledTooltip(): ?string
    {
        $messages = [];

        if (blank($this->data['alasan'] ?? null)) {
            $messages[] = 'Pilih alasan retur';
        }

        if (blank($this->data['tanggal_retur'] ?? null)) {
            $messages[] = 'Pilih tanggal retur';
        }

        if (empty($this->details)) {
            $messages[] = 'Tambahkan minimal 1 item obat';
        }

        return filled($messages)
            ? 'Lengkapi terlebih dahulu: '.implode(', ', $messages)
            : null;
    }

    protected function prosesSimpan(string $status): void
    {
        $this->targetStatus = $status;
        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;

        // Auto-generate nomor retur
        if (blank($data['nomor_retur'] ?? null)) {
            $data['nomor_retur'] = ReturObat::generateNomorRetur();
        }

        // Set default tanggal retur
        if (blank($data['tanggal_retur'] ?? null)) {
            $data['tanggal_retur'] = now()->format('Y-m-d');
        }

        // Set status dari targetStatus
        $data['status'] = $this->targetStatus ?? 'draft';

        // Auto-set tipe_retur dari auth user
        $data['tipe_retur'] = match ($userFaskes?->tipe) {
            'puskesmas' => 'puskesmas_ke_gudang',
            'pustu' => 'pustu_ke_puskesmas',
            default => 'gudang_ke_supplier',
        };

        // Auto-set fasilitas_pengirim_id dari auth user (NULL untuk admin)
        $data['fasilitas_pengirim_id'] = $user?->fasilitas_kesehatan_id;

        // Auto-set fasilitas_penerima_id dari distribusi (jika ada)
        if (filled($data['distribusi_id'] ?? null)) {
            $distribusi = DistribusiObat::find($data['distribusi_id']);
            $data['fasilitas_penerima_id'] = $distribusi?->fasilitas_penerima_id;
        }

        // Auto-set supplier_id dari penerimaan (jika ada)
        if (filled($data['penerimaan_id'] ?? null)) {
            $penerimaan = PenerimaanStok::find($data['penerimaan_id']);
            $data['supplier_id'] = $penerimaan?->supplier_id;
        }

        unset($data['details']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        foreach ($this->details as $detail) {
            $record->details()->create([
                'obat_id' => $detail['obat_id'],
                'batch_id' => $detail['batch_id'] ?? null,
                'jumlah_retur' => $detail['jumlah_retur'],
                'bukti_foto' => $detail['bukti_foto'] ?? null,
                'catatan' => $detail['catatan'] ?? null,
            ]);
        }

        // Proses stok jika langsung selesai
        if ($this->targetStatus === 'selesai') {
            app(StokService::class)->prosesReturDiterima(
                $record->fresh('details')
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
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
