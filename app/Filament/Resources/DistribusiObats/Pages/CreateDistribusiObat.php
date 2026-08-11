<?php

namespace App\Filament\Resources\DistribusiObats\Pages;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPermintaanObat;
use App\Models\DistribusiObat;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Services\FefoService;
use App\Services\NomorFormatService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Stokobat\Boxicons\Boxicon;

class CreateDistribusiObat extends CreateRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = DistribusiObatResource::class;

    protected static bool $canCreateAnother = false;

    public bool $isKirim = false;

    public bool $isLoadingItems = false;

    public array $details = [];

    /** @var array<int, array<string, mixed>> Expanded detail rows ready for DB insert. */
    private array $expandedDetails = [];

    #[Url]
    public ?int $permintaan_id = null;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                TextColumn::make('obat_name')
                    ->label('Obat'),
                TextColumn::make('satuan_nama')
                    ->label('Satuan'),
                TextColumn::make('kategori_nama')
                    ->label('Kategori'),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->alignEnd()
                    ->numeric()
                    ->summarize(Sum::make('total_jumlah')->label('Total Jumlah')->hiddenLabel()->using(fn () => collect($this->details)->sum('jumlah'))),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Distribusi')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon(Boxicon::EditAlt)
                    ->iconButton()
                    ->modalHeading('Edit Item Distribusi')
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon(Boxicon::Trash)
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Distribusi')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function getItemFormSchema(): array
    {
        return [
            Select::make('obat_id')
                ->label('Obat')
                ->options(Obat::query()->where('status', 'aktif')->pluck('nama_obat', 'id'))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $set, $state): void {
                    if (blank($state)) {
                        $set('satuan_nama', null);
                        $set('kategori_nama', null);

                        return;
                    }
                    $obat = Obat::find($state);
                    $set('satuan_nama', $obat?->satuan ?? '-');
                    $set('kategori_nama', $obat?->kategori ?? '-');
                }),
            TextInput::make('jumlah')
                ->label('Jumlah')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(1),
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
            'satuan_nama' => $record['satuan_nama'] ?? null,
            'kategori_nama' => $record['kategori_nama'] ?? null,
            'jumlah' => $record['jumlah'] ?? 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addItem(array $data): void
    {
        $obat = Obat::find($data['obat_id']);
        $maxKey = collect($this->details)->max('_key') ?? -1;

        $this->details[] = [
            '_key' => $maxKey + 1,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obat?->nama_obat ?? '',
            'satuan_nama' => $obat?->satuan ?? '-',
            'kategori_nama' => $obat?->kategori ?? '-',
            'jumlah' => (int) ($data['jumlah'] ?? 1),
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

        $obat = Obat::find($data['obat_id']);

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obat?->nama_obat ?? '',
            'satuan_nama' => $obat?->satuan ?? '-',
            'kategori_nama' => $obat?->kategori ?? '-',
            'jumlah' => (int) ($data['jumlah'] ?? 1),
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

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill();

        $this->callHook('afterFill');

        if (blank($this->permintaan_id)) {
            return;
        }

        $permintaan = PermintaanObat::with('details.obat')->find($this->permintaan_id);
        if (! $permintaan || $permintaan->status !== 'disetujui') {
            return;
        }

        $this->form->rawState([
            ...$this->form->getRawState(),
            'permintaan_id' => $permintaan->id,
            'fasilitas_penerima_id' => $permintaan->fasilitas_pengirim_id,
        ]);
        $hydratedDefaultState = null;
        $this->form->hydrateState($hydratedDefaultState, shouldCallHydrationHooks: false);

        $this->loadPermintaanItems($permintaan->id);
    }

    protected function loadPermintaanItems(?int $permintaanId): void
    {
        $this->isLoadingItems = true;

        try {
            if (blank($permintaanId)) {
                $this->details = [];
                $this->flushCachedTableRecords();

                return;
            }

            $permintaan = PermintaanObat::with('details.obat')->find($permintaanId);

            if (! $permintaan || $permintaan->status !== 'disetujui') {
                Notification::make()
                    ->title('Permintaan tidak valid atau belum disetujui.')
                    ->danger()
                    ->send();
                $this->details = [];
                $this->flushCachedTableRecords();

                return;
            }

            $this->details = [];

            foreach ($permintaan->details as $detail) {
                $this->details[] = [
                    '_key' => $detail->obat_id,
                    'obat_id' => $detail->obat_id,
                    'obat_name' => $detail->obat?->nama_obat ?? '',
                    'satuan_nama' => $detail->info_satuan,
                    'kategori_nama' => $detail->info_kategori,
                    'jumlah' => (int) ($detail->jumlah_disetujui ?? $detail->jumlah_diminta),
                ];
            }

            $this->form->rawState([
                ...$this->form->getRawState(),
                'fasilitas_penerima_id' => $permintaan->fasilitas_pengirim_id,
            ]);

            $this->flushCachedTableRecords();
            Notification::make()
                ->title("Permintaan {$permintaan->nomor_permintaan} dimuat ({$permintaan->details->count()} item)")
                ->success()
                ->send();
        } finally {
            $this->isLoadingItems = false;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;
        $isSuperAdmin = $user?->hasRole('super_admin');

        $data['pengirim_id'] = $user->id;

        if (! $isSuperAdmin) {
            if (filled($userFaskes) && $userFaskes->tipe === 'puskesmas') {
                $data['fasilitas_pengirim_id'] ??= $userFaskes->id;
                $data['tipe_distribusi'] ??= 'puskesmas_ke_pustu';
            } else {
                // admin_dinas — tidak memiliki faskes
                $data['fasilitas_pengirim_id'] ??= null;
                $data['tipe_distribusi'] ??= 'dinas_ke_puskesmas';
            }

            $data['status'] = $this->isKirim ? 'dalam_pengiriman' : 'draft';
        }

        // Expand details from $this->details instead of $data['details']
        try {
            $this->expandedDetails = self::allocateDetails(
                $this->details,
                $data['fasilitas_pengirim_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Stok tidak mencukupi')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

    private function generateNomorSuratJalan(?int $fasilitasId = null): string
    {
        return NomorFormatService::generate('distribusi_obat', $fasilitasId);
    }

    /**
     * Expand detail rows by allocating quantities across batches using obat's metode.
     *
     * If a user-selected batch has sufficient stock, the row is kept as-is.
     * Otherwise, the quantity is split across multiple metode-ordered batches.
     *
     * @param  array<int, array<string, mixed>>  $details
     * @return array<int, array<string, mixed>>
     */
    public static function allocateDetails(array $details, ?int $fasilitasPengirimId): array
    {
        $fefo = app(FefoService::class);
        $result = [];
        $errors = [];

        foreach ($details as $detail) {
            $obatId = (int) ($detail['obat_id'] ?? 0);
            $jumlah = (int) ($detail['jumlah'] ?? 0);

            if ($obatId <= 0 || $jumlah <= 0) {
                continue;
            }

            $obat = Obat::find($obatId);
            $metode = $obat?->metode_stok->value ?? 'fefo';

            if (! $fefo->hasSufficientStock($obatId, $jumlah, $fasilitasPengirimId, $metode)) {
                $available = $fefo->getAvailableBatches($obatId, $fasilitasPengirimId, $metode)->sum('jumlah');
                $namaObat = $obat?->nama_obat ?? "ID {$obatId}";
                $errors[] = "Stok {$namaObat} tidak mencukupi (diminta {$jumlah}, tersedia {$available}).";

                continue;
            }

            $preferredBatchId = ! blank($detail['batch_id'] ?? null)
                ? (int) $detail['batch_id']
                : null;

            if ($preferredBatchId) {
                $batch = BatchStok::find($preferredBatchId);
                if ($batch && $batch->jumlah >= $jumlah) {
                    $result[] = [
                        'obat_id' => $obatId,
                        'batch_id' => $preferredBatchId,
                        'jumlah' => $jumlah,
                    ];

                    continue;
                }
            }

            $allocation = $fefo->allocate($obatId, $jumlah, $fasilitasPengirimId, $metode);

            foreach ($allocation as $alloc) {
                $result[] = [
                    'obat_id' => $obatId,
                    'batch_id' => $alloc['batch_id'],
                    'jumlah' => $alloc['jumlah'],
                ];
            }
        }

        if (! empty($errors)) {
            throw new \RuntimeException(implode(' | ', $errors));
        }

        return $result;
    }

    protected function getFormActions(): array
    {
        return [
            parent::getCancelFormAction()
                ->label('Batal')
                ->icon(Boxicon::X)
                ->iconPosition('after'),
            parent::getCreateFormAction()
                ->color('gray')
                ->label('Simpan Draft')
                ->tooltip('Simpan sebagai draft')
                ->icon(Boxicon::Save)
                ->iconPosition('after')
                ->extraAttributes([
                    'class' => 'ml-auto',
                ]),
            Action::make('kirim')
                ->label('Kirim Distribusi')
                ->tooltip('Kirim distribusi obat')
                ->color('primary')
                ->icon(Boxicon::Send)
                ->iconPosition('after')
                ->action(fn () => $this->createWithKirim()),
        ];
    }

    public function createWithKirim(): void
    {
        $this->isKirim = true;
        $this->create();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $data['nomor_surat_jalan'] = $this->generateNomorSuratJalan($data['fasilitas_pengirim_id'] ?? null);
                $record = parent::handleRecordCreation($data);

                foreach ($this->expandedDetails as $detail) {
                    $record->details()->create($detail);
                }

                if ($record->permintaan) {
                    $record->permintaan->update(['status' => 'sedang_didistribusi']);
                }

                if ($this->isKirim) {
                    $this->updateJumlahDikirim($record);
                    $this->notifyPenerima($record);
                }

                return $record;
            } catch (QueryException $e) {
                $lastError = $e;
                if (str_contains($e->getMessage(), 'nomor_surat_jalan') && $attempt < $maxRetries) {
                    continue;
                }
                throw $e;
            }
        }

        throw $lastError;
    }

    private function updateJumlahDikirim(DistribusiObat $record): void
    {
        $totals = DetailDistribusiObat::whereHas('distribusi', fn ($q) => $q->where('permintaan_id', $record->permintaan_id)
        )
            ->selectRaw('obat_id, SUM(jumlah) as total_jumlah')
            ->groupBy('obat_id')
            ->pluck('total_jumlah', 'obat_id');

        foreach ($totals as $obatId => $totalJumlah) {
            DetailPermintaanObat::where('permintaan_id', $record->permintaan_id)
                ->where('obat_id', $obatId)
                ->update(['jumlah_dikirim' => $totalJumlah]);
        }
    }

    private function notifyPenerima(DistribusiObat $record): void
    {
        app(NotificationService::class)->notifyFaskesUsers(
            $record->fasilitas_penerima_id,
            'Distribusi Obat Dikirim',
            "Distribusi {$record->nomor_surat_jalan} sedang dalam pengiriman ke ".($record->fasilitasPenerima?->nama ?? 'faskes tujuan').'.',
            DistribusiObatResource::getUrl('view', ['record' => $record->id]),
            icon: 'heroicon-o-truck',
            color: 'info',
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
