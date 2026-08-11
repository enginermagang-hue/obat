<?php

namespace App\Filament\Resources\LaporanRkos\Concerns;

use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Services\LaporanRkoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

trait ManagesRkoDetails
{
    public array $details = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->details))
            ->paginated(false)
            ->columns([
                IconColumn::make('prediksi_id')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn (array $record): string => $record['prediksi_id'] ? 'Digenerate dari Prediksi' : 'Manual / Pemakaian'),
                TextColumn::make('obat_name')
                    ->label('Obat')
                    ->wrap(),
                TextColumn::make('pemakaian_tahun_sebelumnya')
                    ->label('Pemakaian Th Lalu')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('rata_rata_pemakaian_bulanan')
                    ->label('Rata-rata/Bln')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('stok_akhir')
                    ->label('Sisa Stok (a)')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('kebutuhan_tahunan')
                    ->label('Kebutuhan 18 bln (c)')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('rencana_kebutuhan')
                    ->label('Rencana Kebutuhan (d)')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('usulan')
                    ->label('Usulan')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('buffer_stock_persen')
                    ->label('Buffer (%)')
                    ->numeric()
                    ->alignEnd()
                    ->suffix('%'),
                TextColumn::make('buffer_stok_qty')
                    ->label('Buffer Qty (e)')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('total_kebutuhan')
                    ->label('Total Kebutuhan (f)')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('harga_perkiraan')
                    ->label('Harga Perkiraan')
                    ->numeric()
                    ->alignEnd()
                    ->prefix('Rp'),
                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->numeric()
                    ->alignEnd()
                    ->prefix('Rp'),
                TextColumn::make('keterangan')
                    ->label('Justifikasi/Keterangan')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->headerActions([
                Action::make('addItem')
                    ->label('Tambah Item')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Item RKO')
                    ->modalWidth(Width::ExtraLarge)
                    ->form(fn (): array => $this->getItemFormSchema())
                    ->action(fn (array $data) => $this->addItem($data)),
            ])
            ->actions([
                Action::make('editItem')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->iconButton()
                    ->modalHeading('Edit Item RKO')
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
                    ->modalHeading('Hapus Item RKO')
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
                                $set('harga_perkiraan', 0);
                                $set('ven_kategori_hidden', null);

                                return;
                            }

                            $obat = Obat::find($state);
                            $harga = (float) ($obat?->harga_satuan
                                ?? $obat?->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
                                ?? 0);
                            $set('harga_perkiraan', $harga);
                            $set('ven_kategori_hidden', $obat?->ven_kategori);

                            self::hitungRumusKemenkes($set, $get);

                            $fasilitasId = $this->data['fasilitas_id'] ?? null;
                            $tahun = (int) ($this->data['periode_tahun'] ?? now()->year);

                            if (filled($fasilitasId)) {
                                $prediksi = PrediksiKebutuhan::query()
                                    ->where('fasilitas_id', $fasilitasId)
                                    ->where('obat_id', $state)
                                    ->where('periode_tahun', $tahun)
                                    ->orderBy('periode_bulan', 'desc')
                                    ->first();

                                if ($prediksi !== null) {
                                    $ciText = "Prediksi AI: {$prediksi->jumlah_prediksi}";
                                    if ($prediksi->confidence_lower !== null && $prediksi->confidence_upper !== null) {
                                        $ciText .= " (range: {$prediksi->confidence_lower} - {$prediksi->confidence_upper})";
                                    }
                                    $set('keterangan', $ciText);
                                }
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('pemakaian_tahun_sebelumnya')
                        ->label('Pemakaian Th Lalu')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('rata_rata_pemakaian_bulanan')
                        ->label('Rata-rata Pemakaian/Bln')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungRumusKemenkes($set, $get)),

                    TextInput::make('stok_akhir')
                        ->label('Sisa Stok (a)')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungRumusKemenkes($set, $get)),

                    TextInput::make('kebutuhan_tahunan')
                        ->label('Kebutuhan 18 bln (c = b x 18)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),

                    TextInput::make('rencana_kebutuhan')
                        ->label('Rencana Kebutuhan (d = c - a)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),

                    TextInput::make('usulan')
                        ->label('Usulan Pengadaan')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungTotalAnggaran($set, $get)),

                    TextInput::make('buffer_stock_persen')
                        ->label('Buffer Stock (%)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0)
                        ->suffix('%'),

                    TextInput::make('buffer_stok_qty')
                        ->label('Buffer Stok (e)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),

                    TextInput::make('total_kebutuhan')
                        ->label('Total Kebutuhan (f = d + e)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),

                    TextInput::make('harga_perkiraan')
                        ->label('Harga Perkiraan')
                        ->numeric()
                        ->required()
                        ->prefix('Rp')
                        ->live()
                        ->afterStateUpdated(fn (Set $set, Get $get) => self::hitungTotalAnggaran($set, $get)),

                    TextInput::make('total_harga')
                        ->label('Total Harga')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->prefix('Rp'),

                    Textarea::make('keterangan')
                        ->label('Justifikasi/Keterangan')
                        ->nullable()
                        ->columnSpanFull(),

                    TextInput::make('ven_kategori_hidden')
                        ->hidden()
                        ->dehydrated(false),

                    TextInput::make('abc_kategori')
                        ->hidden()
                        ->dehydrated(false),
                ]),
        ];
    }

    protected function getItemFormData(array $record): array
    {
        return [
            '_key' => $record['_key'] ?? null,
            'obat_id' => $record['obat_id'] ?? null,
            'pemakaian_tahun_sebelumnya' => $record['pemakaian_tahun_sebelumnya'] ?? 0,
            'rata_rata_pemakaian_bulanan' => $record['rata_rata_pemakaian_bulanan'] ?? 0,
            'stok_akhir' => $record['stok_akhir'] ?? 0,
            'kebutuhan_tahunan' => $record['kebutuhan_tahunan'] ?? 0,
            'rencana_kebutuhan' => $record['rencana_kebutuhan'] ?? 0,
            'usulan' => $record['usulan'] ?? 0,
            'buffer_stock_persen' => $record['buffer_stock_persen'] ?? 0,
            'buffer_stok_qty' => $record['buffer_stok_qty'] ?? 0,
            'total_kebutuhan' => $record['total_kebutuhan'] ?? 0,
            'harga_perkiraan' => $record['harga_perkiraan'] ?? 0,
            'total_harga' => $record['total_harga'] ?? 0,
            'keterangan' => $record['keterangan'] ?? null,
            'ven_kategori_hidden' => $record['ven_kategori_hidden'] ?? null,
            'abc_kategori' => $record['abc_kategori'] ?? null,
            'prediksi_id' => $record['prediksi_id'] ?? null,
        ];
    }

    protected function addItem(array $data): void
    {
        $obatId = (int) $data['obat_id'];
        $obat = Obat::find($obatId);
        $obatName = $obat?->nama_obat ?? '';
        $ven = $data['ven_kategori_hidden'] ?? $obat?->ven_kategori;

        $rataRata = (int) ($data['rata_rata_pemakaian_bulanan'] ?? 0);
        $stokAkhir = (int) ($data['stok_akhir'] ?? 0);
        $harga = (float) ($data['harga_perkiraan'] ?? 0);
        $usulan = (int) ($data['usulan'] ?? 0);

        $kebutuhanTahunan = $rataRata * 18;
        $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);

        $bufferPersen = self::getBufferPersenByVen($ven);
        $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
        $totalKebutuhan = $rencanaKebutuhan + $bufferQty;

        if ($usulan === 0) {
            $usulan = $totalKebutuhan;
        }

        $totalHarga = $usulan * $harga;

        $this->details[] = [
            '_key' => count($this->details),
            'id' => null,
            'obat_id' => $obatId,
            'obat_name' => $obatName,
            'pemakaian_tahun_sebelumnya' => (int) ($data['pemakaian_tahun_sebelumnya'] ?? 0),
            'rata_rata_pemakaian_bulanan' => $rataRata,
            'stok_akhir' => $stokAkhir,
            'kebutuhan_tahunan' => $kebutuhanTahunan,
            'rencana_kebutuhan' => $rencanaKebutuhan,
            'usulan' => $usulan,
            'buffer_stock_persen' => $bufferPersen,
            'buffer_stok_qty' => $bufferQty,
            'total_kebutuhan' => $totalKebutuhan,
            'harga_perkiraan' => $harga,
            'total_harga' => $totalHarga,
            'keterangan' => $data['keterangan'] ?? null,
            'ven_kategori_hidden' => $ven,
            'abc_kategori' => null,
            'prediksi_id' => null,
        ];

        $this->flushCachedTableRecords();
        $this->updateTotalAnggaran();
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
        $ven = $data['ven_kategori_hidden'] ?? $obat?->ven_kategori;

        $rataRata = (int) ($data['rata_rata_pemakaian_bulanan'] ?? 0);
        $stokAkhir = (int) ($data['stok_akhir'] ?? 0);
        $harga = (float) ($data['harga_perkiraan'] ?? 0);
        $usulan = (int) ($data['usulan'] ?? 0);

        $kebutuhanTahunan = $rataRata * 18;
        $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);

        $bufferPersen = self::getBufferPersenByVen($ven);
        $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
        $totalKebutuhan = $rencanaKebutuhan + $bufferQty;

        if ($usulan === 0) {
            $usulan = $totalKebutuhan;
        }

        $totalHarga = $usulan * $harga;

        $this->details[$key] = [
            '_key' => $this->details[$key]['_key'] ?? $key,
            'id' => $this->details[$key]['id'] ?? null,
            'obat_id' => $obatId,
            'obat_name' => $obatName,
            'pemakaian_tahun_sebelumnya' => (int) ($data['pemakaian_tahun_sebelumnya'] ?? 0),
            'rata_rata_pemakaian_bulanan' => $rataRata,
            'stok_akhir' => $stokAkhir,
            'kebutuhan_tahunan' => $kebutuhanTahunan,
            'rencana_kebutuhan' => $rencanaKebutuhan,
            'usulan' => $usulan,
            'buffer_stock_persen' => $bufferPersen,
            'buffer_stok_qty' => $bufferQty,
            'total_kebutuhan' => $totalKebutuhan,
            'harga_perkiraan' => $harga,
            'total_harga' => $totalHarga,
            'keterangan' => $data['keterangan'] ?? null,
            'ven_kategori_hidden' => $ven,
            'abc_kategori' => $this->details[$key]['abc_kategori'] ?? null,
            'prediksi_id' => $this->details[$key]['prediksi_id'] ?? null,
        ];

        $this->flushCachedTableRecords();
        $this->updateTotalAnggaran();
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
        $this->updateTotalAnggaran();
    }

    private static function hitungRumusKemenkes(Set $set, Get $get): void
    {
        $rataRata = (int) ($get('rata_rata_pemakaian_bulanan') ?? 0);
        $stokAkhir = (int) ($get('stok_akhir') ?? 0);

        $kebutuhanTahunan = $rataRata * 18;
        $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);

        $ven = $get('ven_kategori_hidden');
        $bufferPersen = self::getBufferPersenByVen($ven);

        $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
        $totalKebutuhan = $rencanaKebutuhan + $bufferQty;

        $set('kebutuhan_tahunan', $kebutuhanTahunan);
        $set('rencana_kebutuhan', $rencanaKebutuhan);
        $set('buffer_stock_persen', $bufferPersen);
        $set('buffer_stok_qty', $bufferQty);
        $set('total_kebutuhan', $totalKebutuhan);

        $usulanSaatIni = (int) ($get('usulan') ?? 0);
        if ($usulanSaatIni === 0 || $usulanSaatIni === $totalKebutuhan) {
            $set('usulan', $totalKebutuhan);
        }

        self::hitungTotalAnggaran($set, $get);
    }

    private static function hitungTotalAnggaran(Set $set, Get $get): void
    {
        $usulan = (int) ($get('usulan') ?? 0);
        $harga = (float) ($get('harga_perkiraan') ?? 0);
        $total = $usulan * $harga;

        $set('total_harga', $total);
    }

    protected function updateTotalAnggaran(): void
    {
        $total = collect($this->details)->sum('total_harga');
        $this->data['total_anggaran'] = $total;
    }

    private static function getBufferPersenByVen(?string $ven): float
    {
        return match ($ven) {
            'V' => 30,
            'E' => 20,
            'N' => 10,
            default => 15,
        };
    }

    public function generateFromPemakaian(): void
    {
        $fasilitasId = $this->data['fasilitas_id'] ?? auth()->user()?->fasilitas_kesehatan_id;
        $periodeRkoTahun = $this->data['periode_tahun'] ?? (int) date('Y');

        if (blank($fasilitasId)) {
            return;
        }

        $preview = app(LaporanRkoService::class)->previewData($fasilitasId, $periodeRkoTahun);

        $this->details = [];

        foreach ($preview as $i => $item) {
            $obat = Obat::find($item['obat_id']);

            $this->details[] = [
                '_key' => $i,
                'id' => null,
                'obat_id' => $item['obat_id'],
                'obat_name' => $obat?->nama_obat ?? '',
                'pemakaian_tahun_sebelumnya' => $item['pemakaian_tahun_sebelumnya'],
                'rata_rata_pemakaian_bulanan' => $item['rata_rata_pemakaian_bulanan'],
                'stok_akhir' => $item['stok_akhir'],
                'kebutuhan_tahunan' => $item['kebutuhan_tahunan'],
                'rencana_kebutuhan' => $item['rencana_kebutuhan'],
                'usulan' => $item['usulan'],
                'buffer_stock_persen' => $item['buffer_stock_persen'],
                'buffer_stok_qty' => $item['buffer_stok_qty'],
                'total_kebutuhan' => $item['total_kebutuhan'],
                'harga_perkiraan' => $item['harga_perkiraan'],
                'total_harga' => $item['total_harga'],
                'keterangan' => $item['keterangan'] ?? null,
                'ven_kategori_hidden' => $item['ven_kategori_hidden'],
                'abc_kategori' => $item['abc_kategori'] ?? null,
                'prediksi_id' => null,
            ];
        }

        $this->flushCachedTableRecords();
        $this->updateTotalAnggaran();
    }

    public function generateFromPrediksi(): void
    {
        $fasilitasId = $this->data['fasilitas_id'] ?? auth()->user()?->fasilitas_kesehatan_id;
        $periodeRkoTahun = $this->data['periode_tahun'] ?? (int) date('Y');

        if (blank($fasilitasId)) {
            return;
        }

        $obatList = Obat::query()
            ->where('status', 'aktif')
            ->orderBy('nama_obat')
            ->get();

        $prediksiMap = PrediksiKebutuhan::query()
            ->where('fasilitas_id', $fasilitasId)
            ->where('periode_tahun', $periodeRkoTahun)
            ->get()
            ->groupBy('obat_id')
            ->map(fn ($items) => $items->sortByDesc('periode_bulan')->first());

        $this->details = [];

        foreach ($obatList as $i => $obat) {
            $prediksi = $prediksiMap->get($obat->id);

            $rataRata = $prediksi?->jumlah_prediksi ?? 0;
            $pemakaianTahunSebelumnya = $rataRata * 12;

            $stokAkhir = StokFaskes::where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obat->id)
                ->value('jumlah') ?? 0;

            $hargaPerkiraan = (float) ($obat->harga_satuan
                ?? $obat->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
                ?? 0);

            $venKategori = $obat->ven_kategori;

            $kebutuhanTahunan = $rataRata * 18;
            $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);

            $bufferPersen = self::getBufferPersenByVen($venKategori);
            $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
            $totalKebutuhan = $rencanaKebutuhan + $bufferQty;

            $usulan = $totalKebutuhan;
            $totalHarga = $usulan * $hargaPerkiraan;

            $keterangan = null;
            if ($prediksi !== null) {
                $metodeLabel = $prediksi->metode === 'ai_gradient_boost' ? 'Gradient Boost' : 'Moving Average';
                $keterangan = "Prediksi: {$prediksi->jumlah_prediksi} ({$metodeLabel}, range: {$prediksi->confidence_lower}–{$prediksi->confidence_upper})";
            }

            $this->details[] = [
                '_key' => $i,
                'id' => null,
                'obat_id' => $obat->id,
                'obat_name' => $obat->nama_obat,
                'pemakaian_tahun_sebelumnya' => $pemakaianTahunSebelumnya,
                'rata_rata_pemakaian_bulanan' => $rataRata,
                'stok_akhir' => $stokAkhir,
                'kebutuhan_tahunan' => $kebutuhanTahunan,
                'rencana_kebutuhan' => $rencanaKebutuhan,
                'usulan' => $usulan,
                'buffer_stock_persen' => $bufferPersen,
                'buffer_stok_qty' => $bufferQty,
                'total_kebutuhan' => $totalKebutuhan,
                'harga_perkiraan' => $hargaPerkiraan,
                'total_harga' => $totalHarga,
                'keterangan' => $keterangan,
                'ven_kategori_hidden' => $venKategori,
                'abc_kategori' => null,
                'prediksi_id' => $prediksi?->id,
            ];
        }

        $this->flushCachedTableRecords();
        $this->updateTotalAnggaran();
    }
}
