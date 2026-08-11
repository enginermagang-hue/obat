<?php

namespace App\Filament\Resources\PemakaianObats\Concerns;

use App\Models\BatchStok;
use App\Models\Obat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Services\FefoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ManagesPemakaianDetails
{
    public array $details = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                TextColumn::make('obat_name')
                    ->label('Obat')
                    ->wrap(),
                TextColumn::make('batch_number')
                    ->label('Batch')
                    ->placeholder('-'),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Obat')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Obat yang Dipakai')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->modalHeading('Edit Item Pemakaian')
                    ->modalWidth(Width::Medium)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Pemakaian')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ])
            ->emptyStateHeading('Belum ada obat')
            ->emptyStateDescription('Klik "Tambah Obat" untuk menambahkan item pertama.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * @return array<int, Component>
     */
    protected function getItemFormSchema(): array
    {
        return [
            Select::make('obat_id')
                ->label('Obat')
                ->options(function (): array {
                    $user = Auth::user();
                    $isSuperAdmin = $user?->hasRole('super_admin');
                    $isAdminGudang = $user?->hasRole('admin_gudang');
                    $fasilitasId = $isSuperAdmin ? null : $user?->fasilitas_kesehatan_id;

                    return Obat::query()
                        ->where('status', 'aktif')
                        ->where(function ($q) use ($fasilitasId, $isSuperAdmin, $isAdminGudang) {
                            if ($isSuperAdmin || $isAdminGudang) {
                                $q->whereHas('stokGudang', fn ($sq) => $sq->where('jumlah', '>', 0));
                            } elseif (filled($fasilitasId)) {
                                $q->whereHas('stokFaskes', fn ($sq) => $sq
                                    ->where('fasilitas_id', $fasilitasId)
                                    ->where('jumlah', '>', 0)
                                );
                            }
                        })
                        ->orderBy('nama_obat')
                        ->pluck('nama_obat', 'id')
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->disabled(fn (Get $get): bool => filled($get('_key')))
                ->helperText('Pilih obat yang tersedia di stok. Batch akan dipilih otomatis berdasarkan metode stok obat.'),

            TextInput::make('jumlah')
                ->label('Jumlah Pakai')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(function (Get $get): int {
                    $obatId = $get('obat_id');

                    if (filled($obatId)) {
                        return self::getAggregateStock((int) $obatId);
                    }

                    return 999999;
                })
                ->helperText(fn (Get $get): string => $this->getStockHelperText($get))
                ->live(),

            Textarea::make('catatan')
                ->label('Catatan per Item')
                ->rows(2)
                ->placeholder('Catatan khusus item ini (opsional)')
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
            'jumlah' => $record['jumlah'] ?? 1,
            'catatan' => $record['catatan'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addItem(array $data): void
    {
        $obatId = (int) $data['obat_id'];
        $jumlah = (int) ($data['jumlah'] ?? 1);

        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $fasilitasId = $isSuperAdmin ? null : $user?->fasilitas_kesehatan_id;

        // Get obat's metode stok for auto-allocation
        $obat = Obat::find($obatId);
        $obatName = $obat?->nama_obat ?? '';
        $metode = $obat?->metode_stok->value ?? 'fefo';

        // Use FefoService to allocate across batches based on metode
        $service = app(FefoService::class);
        $allocations = $service->allocate($obatId, $jumlah, $fasilitasId, $metode);

        // Validate that allocation fully satisfies requested quantity
        $allocatedTotal = collect($allocations)->sum('jumlah');

        if ($allocatedTotal < $jumlah) {
            Notification::make()
                ->title('Stok Tidak Mencukupi')
                ->body("Obat {$obatName} hanya tersedia {$allocatedTotal} unit dari {$jumlah} unit yang diminta.")
                ->warning()
                ->send();

            return;
        }

        // Create one detail row per batch allocation
        foreach ($allocations as $allocation) {
            $batch = BatchStok::find($allocation['batch_id']);

            $this->details[] = [
                '_key' => count($this->details),
                'id' => null,
                'obat_id' => $obatId,
                'obat_name' => $obatName,
                'batch_id' => $allocation['batch_id'],
                'batch_number' => $batch?->batch_number ?? '',
                'jumlah' => $allocation['jumlah'],
                'catatan' => $data['catatan'] ?? null,
            ];
        }

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

        // obat_id field is disabled in edit mode, so it won't be in $data
        $obatId = $data['obat_id'] ?? $record['obat_id'] ?? null;
        $obatName = Obat::find($obatId)?->nama_obat ?? '';
        // Keep existing batch_id, don't take from form (batch not in form anymore)
        $batchId = $this->details[$key]['batch_id'] ?? $record['batch_id'] ?? null;
        $batchNumber = BatchStok::find($batchId)?->batch_number ?? '';

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => (int) $obatId,
            'obat_name' => $obatName,
            'batch_id' => $batchId,
            'batch_number' => $batchNumber,
            'jumlah' => (int) ($data['jumlah'] ?? 1),
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

    private static function getAggregateStock(int $obatId): int
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $fasilitasId = $isSuperAdmin ? null : $user?->fasilitas_kesehatan_id;

        if (filled($fasilitasId)) {
            return StokFaskes::where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obatId)
                ->value('jumlah') ?? 0;
        }

        return StokGudang::where('obat_id', $obatId)
            ->value('jumlah') ?? 0;
    }

    private function getStockHelperText(Get $get): string
    {
        $obatId = $get('obat_id');

        if (blank($obatId)) {
            return 'Stok tersedia: ? unit';
        }

        $stock = self::getAggregateStock((int) $obatId);

        return 'Stok tersedia: '.number_format($stock, 0, ',', '.').' unit';
    }

    protected function getSummaryForConfirm(): string
    {
        $totalItems = count($this->details);
        $totalQty = collect($this->details)->sum('jumlah');
        $obatNames = collect($this->details)
            ->pluck('obat_name')
            ->unique()
            ->values()
            ->implode(', ');

        return "{$totalItems} item obat (total {$totalQty} unit)\n\nObat: {$obatNames}\n\nStok akan berkurang otomatis saat disimpan.";
    }
}
