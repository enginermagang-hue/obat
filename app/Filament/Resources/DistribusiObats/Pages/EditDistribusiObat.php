<?php

namespace App\Filament\Resources\DistribusiObats\Pages;

use App\Filament\Pages\CetakPdfPage;
use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPermintaanObat;
use App\Models\DistribusiObat;
use App\Models\Obat;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EditDistribusiObat extends EditRecord implements HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = DistribusiObatResource::class;

    public bool $isKirim = false;

    public array $details = [];

    /** @var array<int, array<string, mixed>> Expanded detail rows ready for DB insert. */
    private array $expandedDetails = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->details = $this->record->details()
            ->get()
            ->map(fn ($detail, int $key): array => [
                '_key' => $key,
                'id' => $detail->id,
                'obat_id' => $detail->obat_id,
                'obat_name' => $detail->obat?->nama_obat ?? '',
                'satuan_nama' => $detail->obat?->satuan ?? '-',
                'kategori_nama' => $detail->obat?->kategori ?? '-',
                'jumlah' => $detail->jumlah,
            ])
            ->toArray();
    }

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
                    ->numeric(),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Distribusi')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data))
                    ->visible(fn (): bool => $this->record?->status === 'draft'),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->modalHeading('Edit Item Distribusi')
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data))
                    ->visible(fn (): bool => $this->record?->status === 'draft'),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Distribusi')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record))
                    ->visible(fn (): bool => $this->record?->status === 'draft'),
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
            'id' => null,
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
            'id' => $this->details[$key]['id'] ?? null,
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

    public function saveWithKirim(): void
    {
        $this->isKirim = true;
        $this->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->isKirim) {
            $data['status'] = 'dalam_pengiriman';
        }

        // Expand details from $this->details instead of $data['details']
        try {
            $this->expandedDetails = CreateDistribusiObat::allocateDetails(
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        // Replace detail rows: delete old, create new with batch_id from FEFO allocation
        $record->details()->delete();
        foreach ($this->expandedDetails as $detail) {
            $record->details()->create($detail);
        }

        // Update status permintaan menjadi sedang_didistribusi
        if ($record->permintaan && $record->permintaan->status !== 'sedang_didistribusi') {
            $record->permintaan->update(['status' => 'sedang_didistribusi']);
        }

        // Update DetailPermintaanObat.jumlah_dikirim hanya saat dikirim
        if ($this->isKirim) {
            $this->updateJumlahDikirim($record);
            $this->notifyPenerima(
                $record,
                'Distribusi Obat Dikirim',
                "Distribusi {$record->nomor_surat_jalan} sedang dalam pengiriman.",
                'info',
            );
        }

        return $record;
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan'),
            Action::make('kirim')
                ->label('Kirim')
                ->color('primary')
                ->visible(fn (): bool => $this->record?->status === 'draft')
                ->action(fn () => $this->saveWithKirim()),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak_faktur')
                ->label('Cetak Faktur')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn (): string => CetakPdfPage::getUrl(['type' => 'faktur-distribusi', 'id' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record?->status !== 'draft'),
            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Distribusi Obat')
                ->modalDescription('Apakah Anda yakin ingin menghapus distribusi obat ini? Status permintaan akan dikembalikan ke Disetujui.')
                ->action(function (): void {
                    $permintaan = $this->record?->permintaan;
                    $this->record->delete();
                    if ($permintaan) {
                        $permintaan->update(['status' => 'disetujui']);
                    }
                    $this->redirect(DistribusiObatResource::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record?->status === 'draft'),
            Action::make('batalkan_pengiriman')
                ->label('Batalkan Pengiriman')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Pengiriman')
                ->modalDescription('Apakah Anda yakin ingin membatalkan pengiriman ini? Status akan dikembalikan ke Draft.')
                ->action(function (): void {
                    DB::transaction(function (): void {
                        $this->record->update(['status' => 'draft']);

                        if ($this->record->permintaan) {
                            $this->record->permintaan->update(['status' => 'disetujui']);
                        }

                        DetailPermintaanObat::where('permintaan_id', $this->record->permintaan_id)
                            ->update(['jumlah_dikirim' => 0]);
                    });

                    $this->notifyPenerima(
                        $this->record,
                        'Distribusi Obat Dibatalkan',
                        "Pengiriman {$this->record->nomor_surat_jalan} dibatalkan oleh pengirim.",
                        'warning',
                    );

                    Notification::make()
                        ->title('Pengiriman dibatalkan')
                        ->success()
                        ->send();

                    $this->redirect(DistribusiObatResource::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record?->status === 'dalam_pengiriman' && $this->isSender()),

        ];
    }

    private function isSender(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('admin_gudang')) {
            return true;
        }

        return $user->hasRole('user') && $user->fasilitas_kesehatan_id === $this->record?->fasilitas_pengirim_id;
    }

    private function notifyPenerima(DistribusiObat $record, string $title, string $body, string $color): void
    {
        app(NotificationService::class)->notifyFaskesUsers(
            $record->fasilitas_penerima_id,
            $title,
            $body,
            DistribusiObatResource::getUrl('view', ['record' => $record->id]),
            icon: 'heroicon-o-truck',
            color: $color,
        );
    }
}
