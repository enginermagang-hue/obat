<?php

namespace App\Filament\Resources\NeracaTahunans\Concerns;

use App\Models\Obat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Stokobat\Boxicons\Boxicon;

trait ManagesNeracaDetails
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
                TextColumn::make('stok_awal')
                    ->label('Stok Awal')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('total_masuk')
                    ->label('Total Masuk')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('total_keluar')
                    ->label('Total Keluar')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('stok_akhir')
                    ->label('Stok Akhir')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('stok_optimum')
                    ->label('Stok Optimum')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('permintaan')
                    ->label('Permintaan')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('nilai_stok')
                    ->label('Nilai Stok')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item Neraca')
                    ->modalWidth(Width::ExtraLarge)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon(Boxicon::Edit)
                    ->iconButton()
                    ->modalHeading('Edit Item Neraca')
                    ->modalWidth(Width::ExtraLarge)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon(Boxicon::Trash)
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item Neraca')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ])
            ->emptyStateHeading('Belum ada item')
            ->emptyStateDescription('Klik "Tambah Item" untuk menambahkan item pertama, atau "Generate dari Stok" untuk mengisi otomatis.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getItemFormSchema(): array
    {
        return [
            Group::make()
                ->columns(2)
                ->schema([
                    Select::make('obat_id')
                        ->label('Nama Obat')
                        ->options(function (): array {
                            $selectedIds = collect($this->details)->pluck('obat_id')->all();

                            return Obat::query()
                                ->where('status', 'aktif')
                                ->whereNotIn('id', $selectedIds)
                                ->orderBy('nama_obat')
                                ->pluck('nama_obat', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            if (blank($state)) {
                                $set('harga_satuan', 0);
                                $set('stok_akhir', 0);

                                return;
                            }

                            $obat = Obat::find($state);
                            $harga = (float) ($obat?->harga_satuan
                                ?? $obat?->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
                                ?? 0);
                            $set('harga_satuan', $harga);

                            $fasilitasId = $this->data['fasilitas_id'] ?? auth()->user()?->fasilitas_kesehatan_id;

                            if (filled($fasilitasId)) {
                                $stokAkhir = StokFaskes::where('fasilitas_id', $fasilitasId)
                                    ->where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                                $set('stok_akhir', $stokAkhir);
                            } else {
                                $stokAkhir = StokGudang::where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                                $set('stok_akhir', $stokAkhir);
                            }

                            self::hitungNilaiStok($set, $get);
                        })
                        ->columnSpanFull(),

                    TextInput::make('stok_awal')
                        ->label('Stok Awal')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('total_masuk')
                        ->label('Total Masuk')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('total_keluar')
                        ->label('Total Keluar')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('stok_akhir')
                        ->label('Stok Akhir')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungNilaiStok($set, $get)),

                    TextInput::make('stok_optimum')
                        ->label('Stok Optimum')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('permintaan')
                        ->label('Permintaan')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('harga_satuan')
                        ->label('Harga Satuan')
                        ->numeric()
                        ->required()
                        ->prefix('Rp')
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungNilaiStok($set, $get)),

                    TextInput::make('nilai_stok')
                        ->label('Nilai Stok')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),

                    Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function getItemFormData(array $record): array
    {
        return [
            '_key' => $record['_key'] ?? null,
            'obat_id' => $record['obat_id'] ?? null,
            'stok_awal' => $record['stok_awal'] ?? 0,
            'total_masuk' => $record['total_masuk'] ?? 0,
            'total_keluar' => $record['total_keluar'] ?? 0,
            'stok_akhir' => $record['stok_akhir'] ?? 0,
            'stok_optimum' => $record['stok_optimum'] ?? 0,
            'permintaan' => $record['permintaan'] ?? 0,
            'harga_satuan' => (float) ($record['harga_satuan'] ?? 0),
            'nilai_stok' => (float) ($record['nilai_stok'] ?? 0),
            'keterangan' => $record['keterangan'] ?? null,
        ];
    }

    protected function addItem(array $data): void
    {
        $obatId = (int) $data['obat_id'];
        $obat = Obat::find($obatId);
        $obatName = $obat?->nama_obat ?? '';

        $stokAkhir = (int) ($data['stok_akhir'] ?? 0);
        $hargaSatuan = (float) ($data['harga_satuan'] ?? 0);
        $nilaiStok = $stokAkhir * $hargaSatuan;

        $this->details[] = [
            '_key' => count($this->details),
            'id' => null,
            'obat_id' => $obatId,
            'obat_name' => $obatName,
            'stok_awal' => (int) ($data['stok_awal'] ?? 0),
            'total_masuk' => (int) ($data['total_masuk'] ?? 0),
            'total_keluar' => (int) ($data['total_keluar'] ?? 0),
            'stok_akhir' => $stokAkhir,
            'stok_optimum' => (int) ($data['stok_optimum'] ?? 0),
            'permintaan' => (int) ($data['permintaan'] ?? 0),
            'harga_satuan' => $hargaSatuan,
            'nilai_stok' => $nilaiStok,
            'keterangan' => $data['keterangan'] ?? null,
        ];

        $this->flushCachedTableRecords();
    }

    protected function editItem(array $record, array $data): void
    {
        $searchKey = $record['_key'] ?? null;
        $key = $searchKey !== null
            ? array_search($searchKey, array_column($this->details, '_key'))
            : false;

        if ($key === false) {
            return;
        }

        $obatId = (int) ($data['obat_id'] ?? $this->details[$key]['obat_id']);
        $obat = Obat::find($obatId);
        $obatName = $obat?->nama_obat ?? '';

        $stokAkhir = (int) ($data['stok_akhir'] ?? 0);
        $hargaSatuan = (float) ($data['harga_satuan'] ?? 0);
        $nilaiStok = $stokAkhir * $hargaSatuan;

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => $obatId,
            'obat_name' => $obatName,
            'stok_awal' => (int) ($data['stok_awal'] ?? 0),
            'total_masuk' => (int) ($data['total_masuk'] ?? 0),
            'total_keluar' => (int) ($data['total_keluar'] ?? 0),
            'stok_akhir' => $stokAkhir,
            'stok_optimum' => (int) ($data['stok_optimum'] ?? 0),
            'permintaan' => (int) ($data['permintaan'] ?? 0),
            'harga_satuan' => $hargaSatuan,
            'nilai_stok' => $nilaiStok,
            'keterangan' => $data['keterangan'] ?? null,
        ];

        $this->flushCachedTableRecords();
    }

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

    private static function hitungNilaiStok(Set $set, Get $get): void
    {
        $stokAkhir = (int) ($get('stok_akhir') ?? 0);
        $hargaSatuan = (float) ($get('harga_satuan') ?? 0);

        $set('nilai_stok', $stokAkhir * $hargaSatuan);
    }

    public function generateFromStok(): void
    {
        $fasilitasId = $this->data['fasilitas_id'] ?? auth()->user()?->fasilitas_kesehatan_id;
        $tahun = (int) ($this->data['tahun'] ?? date('Y'));

        if (blank($fasilitasId) && blank(auth()->user()?->fasilitas_kesehatan_id)) {
            $fasilitasId = null;
        }

        $baseQuery = RiwayatStok::query()->when(
            is_null($fasilitasId),
            fn ($q) => $q->whereNull('fasilitas_id'),
            fn ($q) => $q->where('fasilitas_id', $fasilitasId),
        );

        $obatIdsFromRiwayat = (clone $baseQuery)
            ->whereYear('tanggal', $tahun)
            ->distinct()
            ->pluck('obat_id');

        $obatIdsFromStok = is_null($fasilitasId)
            ? StokGudang::where('jumlah', '>', 0)->pluck('obat_id')
            : StokFaskes::where('fasilitas_id', $fasilitasId)
                ->where('jumlah', '>', 0)
                ->pluck('obat_id');

        $obatIds = $obatIdsFromRiwayat
            ->merge($obatIdsFromStok)
            ->unique()
            ->values();

        if ($obatIds->isEmpty()) {
            return;
        }

        $obatMap = Obat::whereIn('id', $obatIds)
            ->pluck('harga_satuan', 'id');

        $this->details = [];

        foreach ($obatIds as $i => $obatId) {
            $obat = Obat::find($obatId);
            $hargaSatuan = (float) ($obatMap->get($obatId) ?? 0);

            $obatBaseQuery = (clone $baseQuery)->where('obat_id', $obatId);

            $stokAwalRecord = (clone $obatBaseQuery)
                ->whereDate('tanggal', '<', "{$tahun}-01-01")
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $stokAwal = $stokAwalRecord?->stok_sesudah ?? 0;

            $totalMasuk = abs((clone $obatBaseQuery)
                ->whereYear('tanggal', $tahun)
                ->whereIn('tipe', ['masuk', 'distribusi_masuk'])
                ->sum('jumlah'));

            $totalKeluar = abs((clone $obatBaseQuery)
                ->whereYear('tanggal', $tahun)
                ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'hilang', 'expired'])
                ->sum('jumlah'));

            $stokAkhirRecord = (clone $obatBaseQuery)
                ->whereYear('tanggal', $tahun)
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($stokAkhirRecord !== null) {
                $stokAkhir = $stokAkhirRecord->stok_sesudah;
            } elseif ($stokAwalRecord === null) {
                $stokAkhir = is_null($fasilitasId)
                    ? (StokGudang::where('obat_id', $obatId)->value('jumlah') ?? 0)
                    : (StokFaskes::where('fasilitas_id', $fasilitasId)->where('obat_id', $obatId)->value('jumlah') ?? 0);
            } else {
                $stokAkhir = $stokAwal;
            }

            $nilaiStok = $hargaSatuan !== null
                ? $stokAkhir * $hargaSatuan
                : 0;

            $stokOptimum = self::hitungStokOptimum((int) $totalKeluar);
            $permintaan = self::hitungPermintaan((int) $totalKeluar, $stokAkhir);

            $this->details[] = [
                '_key' => $i,
                'id' => null,
                'obat_id' => $obatId,
                'obat_name' => $obat?->nama_obat ?? '',
                'stok_awal' => (int) $stokAwal,
                'total_masuk' => (int) $totalMasuk,
                'total_keluar' => (int) $totalKeluar,
                'stok_akhir' => (int) $stokAkhir,
                'stok_optimum' => $stokOptimum,
                'permintaan' => $permintaan,
                'harga_satuan' => $hargaSatuan,
                'nilai_stok' => $nilaiStok,
                'keterangan' => null,
            ];
        }

        $this->flushCachedTableRecords();
    }

    private static function hitungStokOptimum(int $totalPemakaian, int $periodeBulan = 12): int
    {
        $rataBulan = max(0, $totalPemakaian / max(1, $periodeBulan));

        return (int) ceil($rataBulan * 1.2);
    }

    private static function hitungPermintaan(int $totalPemakaian, int $stokAkhir, int $periodeBulan = 12): int
    {
        $rataBulan = max(0, $totalPemakaian / max(1, $periodeBulan));

        return max(0, (int) ceil($rataBulan * 3) - $stokAkhir);
    }

    protected function getTotalNilaiStok(): float
    {
        return collect($this->details)->sum('nilai_stok');
    }
}
