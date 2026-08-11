<?php

namespace App\Filament\Resources\LaporanLplpos\Concerns;

use App\Models\Obat;
use App\Services\LaporanLplpoService;
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

trait ManagesLplpoDetails
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
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    }),
                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->placeholder('-'),
                TextColumn::make('stok_awal')
                    ->label('Stok Awal')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('jumlah_masuk')
                    ->label('Penerimaan')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('persediaan')
                    ->label('Persediaan')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('jumlah_keluar')
                    ->label('Pemakaian')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('sisa_stok')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('stok_optimum')
                    ->label('Stok Optimum')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('permintaan_selanjutnya')
                    ->label('Permintaan')
                    ->numeric()
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
                    ->modalHeading('Tambah Item LPLPO')
                    ->modalWidth(Width::Large)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->iconButton()
                    ->modalHeading('Edit Item LPLPO')
                    ->modalWidth(Width::ExtraLarge)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->fillForm(fn (array $record): array => $this->getItemFormData($record))
                    ->action(fn (array $data, array $record) => $this->editItem($record, $data)),
                Action::make('deleteItem')
                    ->label('Hapus')
                    ->icon('heroicon-m-trash')
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Item LPLPO')
                    ->modalDescription('Apakah Anda yakin ingin menghapus item ini?')
                    ->action(fn (array $record) => $this->deleteItem($record)),
            ])
            ->emptyStateHeading('Belum ada item')
            ->emptyStateDescription('Klik "Tambah Item" untuk menambahkan item pertama.')
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
                        ->options(fn (): array => Obat::pluck('nama_obat', 'id')->all())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                            if (blank($state)) {
                                $set('satuan', '');

                                return;
                            }
                            $obat = Obat::find($state);
                            $set('satuan', $obat?->satuan ?? '');
                        })
                        ->columnSpanFull(),
                    TextInput::make('satuan')
                        ->label('Satuan')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('stok_awal')
                        ->label('Stok Awal')
                        ->numeric()
                        ->step(10)
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::hitungSisaStok($set, $get)),
                    TextInput::make('jumlah_masuk')
                        ->label('Penerimaan')
                        ->numeric()
                        ->step(10)
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::hitungSisaStok($set, $get)),
                    TextInput::make('persediaan')
                        ->label('Persediaan')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->default(0),
                    TextInput::make('jumlah_keluar')
                        ->label('Pemakaian')
                        ->numeric()
                        ->step(10)
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            static::hitungSisaStok($set, $get);
                            static::hitungStokOptimumDanPermintaan($set, $get);
                        }),
                    TextInput::make('sisa_stok')
                        ->label('Sisa Stok')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('stok_optimum')
                        ->label('Stok Optimum')
                        ->numeric()
                        ->step(10)
                        ->default(0)
                        ->live(),
                    TextInput::make('permintaan_selanjutnya')
                        ->label('Permintaan')
                        ->numeric()
                        ->step(10)
                        ->required()
                        ->default(0),
                    Textarea::make('keterangan')
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
            'satuan' => $record['satuan'] ?? '',
            'stok_awal' => $record['stok_awal'] ?? 0,
            'jumlah_masuk' => $record['jumlah_masuk'] ?? 0,
            'persediaan' => $record['persediaan'] ?? 0,
            'jumlah_keluar' => $record['jumlah_keluar'] ?? 0,
            'sisa_stok' => $record['sisa_stok'] ?? 0,
            'stok_optimum' => $record['stok_optimum'] ?? 0,
            'permintaan_selanjutnya' => $record['permintaan_selanjutnya'] ?? 0,
            'keterangan' => $record['keterangan'] ?? null,
        ];
    }

    protected function addItem(array $data): void
    {
        $obatName = Obat::find($data['obat_id'])?->nama_obat ?? '';
        $satuan = $data['satuan'] ?? '';

        $stokAwal = (int) ($data['stok_awal'] ?? 0);
        $jumlahMasuk = (int) ($data['jumlah_masuk'] ?? 0);
        $jumlahKeluar = (int) ($data['jumlah_keluar'] ?? 0);
        $sisaStok = max(0, $stokAwal + $jumlahMasuk - $jumlahKeluar);

        $this->details[] = [
            '_key' => count($this->details),
            'id' => null,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obatName,
            'satuan' => $satuan,
            'stok_awal' => $stokAwal,
            'jumlah_masuk' => $jumlahMasuk,
            'persediaan' => $stokAwal + $jumlahMasuk,
            'jumlah_keluar' => $jumlahKeluar,
            'sisa_stok' => $sisaStok,
            'stok_optimum' => (int) ($data['stok_optimum'] ?? 0),
            'permintaan_selanjutnya' => (int) ($data['permintaan_selanjutnya'] ?? 0),
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

        $obatName = Obat::find($data['obat_id'])?->nama_obat ?? '';
        $satuan = $data['satuan'] ?? '';

        $stokAwal = (int) ($data['stok_awal'] ?? 0);
        $jumlahMasuk = (int) ($data['jumlah_masuk'] ?? 0);
        $jumlahKeluar = (int) ($data['jumlah_keluar'] ?? 0);
        $sisaStok = max(0, $stokAwal + $jumlahMasuk - $jumlahKeluar);

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => (int) $data['obat_id'],
            'obat_name' => $obatName,
            'satuan' => $satuan,
            'stok_awal' => $stokAwal,
            'jumlah_masuk' => $jumlahMasuk,
            'persediaan' => $stokAwal + $jumlahMasuk,
            'jumlah_keluar' => $jumlahKeluar,
            'sisa_stok' => $sisaStok,
            'stok_optimum' => (int) ($data['stok_optimum'] ?? 0),
            'permintaan_selanjutnya' => (int) ($data['permintaan_selanjutnya'] ?? 0),
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

    public function generateFromRiwayat(): void
    {
        $fasilitasId = $this->data['fasilitas_id'] ?? auth()->user()?->fasilitas_kesehatan_id;
        $tahun = $this->data['periode_tahun'] ?? (int) date('Y');
        $bulan = $this->data['periode_bulan'] ?? (int) date('n');

        if (blank($fasilitasId)) {
            return;
        }

        $preview = app(LaporanLplpoService::class)->previewData($fasilitasId, $tahun, $bulan);

        $this->details = [];

        foreach ($preview as $i => $item) {
            $obat = Obat::find($item['obat_id']);

            $this->details[] = [
                '_key' => $i,
                'id' => null,
                'obat_id' => $item['obat_id'],
                'obat_name' => $obat?->nama_obat ?? '',
                'satuan' => $obat?->satuan ?? '',
                'stok_awal' => $item['stok_awal'],
                'jumlah_masuk' => $item['jumlah_masuk'],
                'persediaan' => $item['stok_awal'] + $item['jumlah_masuk'],
                'jumlah_keluar' => $item['jumlah_keluar'],
                'sisa_stok' => $item['sisa_stok'],
                'stok_optimum' => $item['stok_optimum'],
                'permintaan_selanjutnya' => $item['permintaan_selanjutnya'],
                'keterangan' => null,
            ];
        }

        $this->flushCachedTableRecords();
    }

    protected static function hitungSisaStok(Set $set, Get $get): void
    {
        $stokAwal = (int) ($get('stok_awal') ?? 0);
        $jumlahMasuk = (int) ($get('jumlah_masuk') ?? 0);
        $jumlahKeluar = (int) ($get('jumlah_keluar') ?? 0);
        $sisaStok = $stokAwal + $jumlahMasuk - $jumlahKeluar;
        $sisaStok = max(0, $sisaStok);

        $set('sisa_stok', $sisaStok);
        $set('persediaan', $stokAwal + $jumlahMasuk);
    }

    protected static function hitungStokOptimumDanPermintaan(Set $set, Get $get): void
    {
        $jumlahKeluar = (int) ($get('jumlah_keluar') ?? 0);
        $sisaStok = (int) ($get('sisa_stok') ?? 0);

        $stokOptimum = (int) ceil(max(0, $jumlahKeluar) * 1.2);
        $set('stok_optimum', $stokOptimum);

        $permintaan = max(0, (int) ceil(max(0, $jumlahKeluar) * 3) - $sisaStok);
        $set('permintaan_selanjutnya', $permintaan);
    }
}
