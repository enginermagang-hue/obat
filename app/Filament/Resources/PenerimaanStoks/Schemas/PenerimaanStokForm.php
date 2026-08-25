<?php

namespace App\Filament\Resources\PenerimaanStoks\Schemas;

use App\Helpers\BatchNumberGenerator;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\SumberDana;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Stokobat\Boxicons\Boxicon;

class PenerimaanStokForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;

        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Utama')
                    ->icon('heroicon-m-information-circle')
                    ->contained(true)
                    ->columns(1)
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('nomor_penerimaan')
                                ->label('Nomor Penerimaan')
                                ->required()
                                ->maxLength(100)
                                ->suffixAction(
                                    Action::make('generateNomorPenerimaan')
                                        ->icon(Boxicon::RefreshCcw)
                                        ->action(function (Set $set) {
                                            $set('nomor_penerimaan', PenerimaanStok::generateNomorPenerimaan());
                                        })
                                ),
                            Select::make('tipe')
                                ->required()
                                ->live()
                                ->options([
                                    'pembelian' => 'Pembelian',
                                    'hibah' => 'Hibah',
                                    'stok_awal' => 'Stok Awal',
                                    'penyesuaian' => 'Penyesuaian',
                                    'distribusi' => 'Distribusi',
                                    'manual' => 'Manual',
                                ]),
                            DatePicker::make('tanggal_penerimaan')
                                ->required()
                                ->default(now()),
                            Hidden::make('fasilitas_id')
                                ->default($userFaskesId),
                            Select::make('distribusi_id')
                                ->label('Distribusi Obat')
                                ->helperText('Pilih distribusi yang sedang dalam pengiriman ke faskes Anda.')
                                ->placeholder('Pilih distribusi...')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->native(false)
                                ->relationship(
                                    'distribusi',
                                    'nomor_surat_jalan',
                                    fn ($query) => $query
                                        ->where('status', 'dalam_pengiriman')
                                        ->when(
                                            $userFaskesId,
                                            fn ($q) => $q->where('fasilitas_penerima_id', $userFaskesId),
                                        ),
                                )
                                ->required(fn (Get $get): bool => $get('tipe') === 'distribusi')
                                ->hidden(fn (Get $get): bool => $get('tipe') !== 'distribusi'),
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::query()->where('status', 'aktif')->pluck('nama', 'id'))
                                ->searchable()
                                ->nullable()
                                ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian')
                                ->createOptionForm([
                                    TextInput::make('nama')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique('suppliers', 'nama'),
                                    Textarea::make('alamat')
                                        ->nullable(),
                                    TextInput::make('telepon')
                                        ->nullable()
                                        ->maxLength(50)
                                        ->tel(),
                                    TextInput::make('email')
                                        ->nullable()
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('npwp')
                                        ->nullable()
                                        ->maxLength(50),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $data['status'] = 'aktif';

                                    return Supplier::create($data)->getKey();
                                })
                                ->createOptionAction(
                                    fn (Action $action) => $action
                                        ->modalHeading('Tambah Supplier 2')
                                        ->modalSubmitActionLabel('Simpan')
                                        ->modalWidth(Width::Small)
                                        ->modalFooterActionsAlignment(Alignment::End)
                                ),
                            Select::make('sumber_dana_id')
                                ->label('Sumber Dana')
                                ->options(SumberDana::query()->where('status', 'aktif')->pluck('nama', 'id'))
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->hidden(fn (Get $get): bool => ! in_array($get('tipe'), ['pembelian', 'manual'], true)),
                            TextInput::make('nomor_po')
                                ->label('Nomor PO')
                                ->nullable()
                                ->maxLength(100)
                                ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian'),
                            TextInput::make('nomor_invoice')
                                ->label('Nomor Invoice')
                                ->nullable()
                                ->maxLength(100)
                                ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian'),
                        ]),
                    ]),

                Hidden::make('user_id')
                    ->default(Auth::id()),

                Section::make('Item Penerimaan')
                    ->contained(true)
                    ->heading('')
                    ->schema([
                        Repeater::make('details')
                            ->hiddenLabel()
                            ->relationship()
                            ->table([
                                TableColumn::make('Obat'),
                                TableColumn::make('Jumlah')
                                    ->alignEnd()
                                    ->width(120),
                                TableColumn::make('Tanggal Expired')
                                    ->width(150),
                                TableColumn::make('Batch Number')
                                    ->width(150),
                                TableColumn::make('Harga Satuan')
                                    ->alignEnd()
                                    ->width(150),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('obat_id')
                                    ->label('Obat')
                                    ->required()
                                    ->searchable()
                                    ->live(onBlur: true)
                                    ->options(fn (): array => Obat::orderBy('nama_obat')->pluck('nama_obat', 'id')->toArray())
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (blank($state)) {
                                            $set('harga_satuan', null);

                                            return;
                                        }

                                        $obat = Obat::find($state);
                                        $harga = (float) ($obat?->harga_satuan
                                            ?? $obat?->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
                                            ?? 0);
                                        $set('harga_satuan', $harga);

                                        if (config('app.batch_number_auto_generate')) {
                                            $set('batch_number', BatchNumberGenerator::generate((int) $state));
                                        }
                                    }),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->default(1)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->extraInputAttributes([
                                        'class' => 'text-right',
                                    ]),
                                DatePicker::make('tanggal_expired')
                                    ->label('Tanggal Expired')
                                    ->required(),
                                TextInput::make('batch_number')
                                    ->label('Batch Number')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('harga_satuan')
                                    ->label('Harga Satuan')
                                    ->nullable()
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                    ->live(onBlur: true)
                                    ->extraInputAttributes([
                                        'class' => 'text-right',
                                    ]),
                            ])
                            ->columns(1)
                            ->reorderable()
                            ->addActionLabel('Tambah Item')
                            ->minItems(1),
                    ]),

                Section::make('Catatan & Ringkasan')
                    ->heading('')
                    ->contained(true)
                    ->schema([
                        Textarea::make('catatan')
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Tambahkan catatan jika ada'),
                    ]),
            ]);
    }

    public static function configureCreate(): Wizard
    {
        $user = Auth::user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;

        return Wizard::make([
            Step::make('Informasi')
                ->description('Pilih tipe & sumber penerimaan')
                ->icon('heroicon-m-information-circle')
                ->schema([
                    Placeholder::make('nav_listener')
                        ->hiddenLabel()
                        ->content(new HtmlString('<div x-on:wizard-navigate.window="step = $event.detail.step"></div>')),
                    Section::make('Informasi Utama')
                        ->contained(false)
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('nomor_penerimaan')
                                    ->label('Nomor Penerimaan')
                                    ->required()
                                    ->maxLength(100)
                                    ->suffixAction(
                                        Action::make('generateNomorPenerimaan')
                                            ->icon(Boxicon::RefreshCcw)
                                            ->action(fn (Set $set) => $set('nomor_penerimaan', PenerimaanStok::generateNomorPenerimaan())),
                                    ),
                                Select::make('tipe')
                                    ->required()
                                    ->live()
                                    ->options([
                                        'pembelian' => 'Pembelian',
                                        'hibah' => 'Hibah',
                                        'stok_awal' => 'Stok Awal',
                                        'penyesuaian' => 'Penyesuaian',
                                        'distribusi' => 'Distribusi',
                                        'manual' => 'Manual',
                                    ]),
                                DatePicker::make('tanggal_penerimaan')
                                    ->required()
                                    ->default(now()),
                                Hidden::make('fasilitas_id')
                                    ->default($userFaskesId),
                                Select::make('distribusi_id')
                                    ->label('Distribusi Obat')
                                    ->helperText('Pilih distribusi yang sedang dalam pengiriman ke faskes Anda.')
                                    ->placeholder('Pilih distribusi...')
                                    // ->searchable()
                                    ->preload()
                                    ->live()
                                    // ->native(false)
                                    ->relationship(
                                        'distribusi',
                                        'nomor_surat_jalan',
                                        fn ($query) => $query
                                            ->where('status', 'dalam_pengiriman')
                                            ->when(
                                                $userFaskesId,
                                                fn ($q) => $q->where('fasilitas_penerima_id', $userFaskesId),
                                            ),
                                    )
                                    ->required(fn (Get $get): bool => $get('tipe') === 'distribusi')
                                    ->hidden(fn (Get $get): bool => $get('tipe') !== 'distribusi'),
                                Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->options(Supplier::query()->where('status', 'aktif')->pluck('nama', 'id'))
                                    // ->searchable()
                                    ->nullable()
                                    ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian')
                                    ->createOptionForm([
                                        TextInput::make('nama')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique('suppliers', 'nama'),
                                        Textarea::make('alamat')
                                            ->nullable(),
                                        TextInput::make('telepon')
                                            ->nullable()
                                            ->maxLength(50)
                                            ->tel(),
                                        TextInput::make('email')
                                            ->nullable()
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('npwp')
                                            ->nullable()
                                            ->maxLength(50),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $data['status'] = 'aktif';

                                        return Supplier::create($data)->getKey();
                                    })
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading('Tambah Supplier')
                                            ->modalSubmitActionLabel('Simpan')
                                            ->modalFooterActionsAlignment(Alignment::End)
                                            ->modalWidth(Width::Medium),
                                    ),
                                Select::make('sumber_dana_id')
                                    ->label('Sumber Dana')
                                    ->options(SumberDana::query()->where('status', 'aktif')->pluck('nama', 'id'))
                                    // ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->hidden(fn (Get $get): bool => ! in_array($get('tipe'), ['pembelian', 'manual'], true)),
                                TextInput::make('nomor_po')
                                    ->label('Nomor PO')
                                    ->nullable()
                                    ->maxLength(100)
                                    ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian'),
                                TextInput::make('nomor_invoice')
                                    ->label('Nomor Invoice')
                                    ->nullable()
                                    ->maxLength(100)
                                    ->hidden(fn (Get $get): bool => $get('tipe') !== 'pembelian'),
                            ]),
                        ]),
                    Hidden::make('user_id')
                        ->default(Auth::id()),
                ]),

            Step::make('Item Obat')
                ->description('Tambah item yang diterima')
                ->icon('heroicon-m-archive-box')
                ->schema([
                    Section::make('Item Penerimaan')
                        ->heading('')
                        ->contained(false)
                        ->schema([
                            Checkbox::make('auto_generate_batch_number')
                                ->label('Auto Generate Batch Number')
                                ->default((bool) config('app.batch_number_auto_generate'))
                                ->live()
                                ->columnSpanFull(),
                            Repeater::make('details')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Obat'),
                                    TableColumn::make('Jumlah')
                                        ->width(150)
                                        ->alignRight(),
                                    TableColumn::make('Tanggal Expired')
                                        ->width(150),
                                    TableColumn::make('Batch Number')
                                        ->width(150),
                                    TableColumn::make('Harga Satuan')
                                        ->width(150)
                                        ->alignRight(),
                                ])
                                ->compact()
                                ->schema([
                                    Select::make('obat_id')
                                        ->label('Obat')
                                        ->required()
                                        // ->searchable()
                                        ->live(onBlur: true)
                                        ->options(fn (): array => Obat::orderBy('nama_obat')->pluck('nama_obat', 'id')->toArray())
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (blank($state)) {
                                                $set('harga_satuan', null);

                                                return;
                                            }

                                            $obat = Obat::find($state);
                                            $harga = (float) ($obat?->harga_satuan
                                                ?? $obat?->batchStok()->where('harga_beli', '>', 0)->avg('harga_beli')
                                                ?? 0);
                                            $set('harga_satuan', $harga);

                                            if (
                                                $get('../../auto_generate_batch_number')
                                                && blank($get('batch_number'))
                                            ) {
                                                $set('batch_number', BatchNumberGenerator::generate((int) $state));
                                            }
                                        }),
                                    TextInput::make('jumlah')
                                        ->label('Jumlah')
                                        ->extraInputAttributes([
                                            'style' => 'text-align: right',
                                        ])
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->default(1)
                                        ->step(10)
                                        ->live(onBlur: true),
                                    DatePicker::make('tanggal_expired')
                                        ->label('Tanggal Expired')
                                        ->required(),
                                    TextInput::make('batch_number')
                                        ->label('Batch Number')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('harga_satuan')
                                        ->label('Harga Satuan')
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                        ->live(onBlur: true)
                                        ->extraInputAttributes([
                                            'style' => 'text-align: right',
                                        ]),
                                ])
                                ->columns(1)
                                ->reorderable(false)
                                ->addActionLabel('Tambah Item')
                                ->minItems(1),
                        ]),
                ]),

            Step::make('Konfirmasi')
                ->description('Catatan & simpan')
                ->icon('heroicon-m-check-circle')
                ->schema([
                    Section::make('Konfirmasi Penerimaan')
                        ->schema([
                            Placeholder::make('ringkasan')
                                ->label('Ringkasan Penerimaan')
                                ->content(function (): HtmlString {
                                    $component = Livewire::current();
                                    $details = $component?->form?->getRawState()['details'] ?? [];
                                    $namaObat = Obat::whereIn(
                                        'id',
                                        collect($details)->pluck('obat_id')->filter()->all(),
                                    )->pluck('nama_obat', 'id')->all();

                                    $rows = '';
                                    $total = 0.0;
                                    foreach (array_values($details) as $i => $d) {
                                        $hargaRaw = $d['harga_satuan'] ?? null;
                                        $hargaNum = filled($hargaRaw)
                                            ? (float) (str_contains((string) $hargaRaw, ',')
                                                ? str_replace(['.', ','], ['', '.'], (string) $hargaRaw)
                                                : (string) $hargaRaw)
                                            : 0.0;
                                        $jumlah = (int) ($d['jumlah'] ?? 0);
                                        $subtotal = $jumlah * $hargaNum;
                                        $total += $subtotal;

                                        $rows .= sprintf(
                                            '<tr class="border-b border-gray-200 dark:border-white/10"><td class="p-2">%d</td><td class="p-2">%s</td><td class="p-2">%s</td><td class="p-2">%s</td><td class="p-2 text-right">%s</td><td class="p-2 text-right">%s</td><td class="p-2 text-right">Rp %s</td></tr>',
                                            $i + 1,
                                            e($namaObat[$d['obat_id'] ?? null] ?? '-'),
                                            e($d['batch_number'] ?? '-'),
                                            e($d['tanggal_expired'] ?? '-'),
                                            e($d['jumlah'] ?? 0),
                                            e($d['harga_satuan'] ?? '-'),
                                            number_format($subtotal, 2, ',', '.'),
                                        );
                                    }

                                    $body = $rows !== ''
                                        ? $rows
                                        : '<tr><td colspan="7" class="p-2 text-center text-gray-500 dark:text-gray-400">Tidak ada item.</td></tr>';

                                    $footer = sprintf(
                                        '<tr class="border-t border-gray-300 dark:border-white/20 font-semibold"><td colspan="6" class="p-2 text-right">Total</td><td class="p-2 text-right">Rp %s</td></tr>',
                                        number_format($total, 2, ',', '.'),
                                    );

                                    $html = '<table class="w-full text-sm border-collapse"><thead><tr class="border-b border-gray-200 dark:border-white/10"><th class="text-left p-2">#</th><th class="text-left p-2">Obat</th><th class="text-left p-2">Batch</th><th class="text-left p-2">Expired</th><th class="text-right p-2">Qty</th><th class="text-right p-2">Harga</th><th class="text-right p-2">Subtotal</th></tr></thead><tbody>'
                                        .$body
                                        .'</tbody><tfoot>'
                                        .$footer
                                        .'</tfoot></table>';

                                    return new HtmlString($html);
                                }),
                            Textarea::make('catatan')
                                ->nullable()
                                ->rows(3),
                        ]),
                ]),
        ])
            ->nextAction(fn (Action $action) => $action->extraAttributes(['style' => 'display:none']))
            ->previousAction(fn (Action $action) => $action->extraAttributes(['style' => 'display:none']));
    }
}
