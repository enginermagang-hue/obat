<?php

namespace App\Filament\Resources\OpnameStoks\Schemas;

use App\Helpers\BatchNumberGenerator;
use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\OpnameStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Stokobat\Boxicons\Boxicon;

class OpnameStokForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('tipe')
                    ->label('Tipe Opname')
                    ->options([
                        'penyesuaian' => 'Penyesuaian (Stok Existing)',
                        'stok_awal' => 'Stok Awal',
                        'stok_baru' => 'Stok Baru',
                    ])
                    ->default('penyesuaian')
                    ->required()
                    ->live(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'selesai' => 'Selesai',
                    ])
                    ->default('draft')
                    ->required(),
                TextInput::make('nomor_opname')
                    ->label('Nomor Opname')
                    ->required()
                    ->maxLength(100)
                    ->suffixAction(
                        Action::make('generate_nomor_opname')
                            ->icon(Boxicon::RefreshCcw)
                            ->action(function (Set $set, $get): void {
                                $fasilitasId = $get('fasilitas_id');
                                $tanggalOpname = $get('tanggal_opname') ? Carbon::parse($get('tanggal_opname')) : now();
                                $tipe = $get('tipe') ?? 'penyesuaian';

                                $record = new OpnameStok([
                                    'fasilitas_id' => $fasilitasId,
                                    'tanggal_opname' => $tanggalOpname,
                                ]);

                                if ($fasilitasId) {
                                    $record->setRelation('fasilitas', FasilitasKesehatan::find($fasilitasId));
                                }

                                $set('nomor_opname', OpnameStok::generateNomorOpname($record, $tipe));
                            })
                    ),
                DatePicker::make('tanggal_opname')
                    ->label('Tanggal Opname')
                    ->required()
                    ->default(now()),
                Textarea::make('catatan')
                    ->label('Catatan')
                    ->nullable()
                    ->columnSpanFull()
                    ->autosize(),
                Hidden::make('fasilitas_id')
                    ->default(auth()->user()->fasilitas_kesehatan_id ?? null),
                Hidden::make('user_id')
                    ->default(auth()->id()),
                Repeater::make('items')
                    ->label('Item Opname')
                    ->columnSpanFull()
                    ->addActionLabel('Tambah Item')
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->table(fn (Get $get) => self::getRepeaterColumn($get))
                    ->compact()
                    ->schema(fn (Get $get) => self::getRepeaterSchema($get)),
            ]);
    }

    protected static function getRepeaterColumn(Get $get): array
    {
        $columns = [
            TableColumn::make('Obat'),
        ];

        if ($get('tipe') === 'penyesuaian') {
            $columns[] = TableColumn::make('Batch')
                ->width(200);
            $columns[] = TableColumn::make('Stok Sistem')
                ->alignEnd()
                ->width(160);
            $columns[] = TableColumn::make('Stok Fisik')
                ->alignEnd()
                ->width(160);
            $columns[] = TableColumn::make('Selisih')
                ->alignEnd()
                ->width(160);
        } else {
            $columns[] = TableColumn::make('Batch')
                ->width(200);
            $columns[] = TableColumn::make('Expired Date')
                ->width(200);
            $columns[] = TableColumn::make('Stok Fisik')
                ->alignEnd()
                ->width(160);
        }

        return $columns;
    }

    protected static function getRepeaterSchema(Get $get): array
    {
        return match ($get('tipe')) {
            'penyesuaian' => [
                Hidden::make('id'),
                Select::make('obat_id')
                    ->label('Obat')
                    ->required()
                    ->searchable()
                    ->live(onBlur: true)
                    ->options(fn (): array => Obat::orderBy('nama_obat')->pluck('nama_obat', 'id')->toArray())
                    ->afterStateUpdated(function ($state, Set $set, $get): void {
                        $stok = 0;

                        if ($state) {
                            $fasilitasId = $get('../../fasilitas_id');
                            if ($fasilitasId) {
                                $stok = StokFaskes::where('fasilitas_id', $fasilitasId)
                                    ->where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                            } else {
                                $stok = StokGudang::where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                            }
                        }

                        $set('stok_sistem', (int) $stok);
                        $set('selisih', 0);
                    }),
                Select::make('batch_id')
                    ->label('Batch')
                    ->searchable()
                    ->live(onBlur: true)
                    ->options(function ($get): array {
                        $obatId = $get('obat_id');
                        if (blank($obatId)) {
                            return [];
                        }

                        $fasilitasId = $get('../../fasilitas_id');
                        $obat = Obat::find($obatId);
                        $metode = $obat?->metode_stok->value ?? 'fefo';

                        $query = BatchStok::query()
                            ->where('obat_id', $obatId)
                            ->when($fasilitasId, fn ($q) => $q->where('fasilitas_id', $fasilitasId), fn ($q) => $q->whereNull('fasilitas_id'))
                            ->where('status', 'tersedia')
                            ->where('jumlah', '>', 0);

                        match ($metode) {
                            'fifo' => $query->orderBy('tanggal_masuk')->orderBy('id'),
                            'lifo' => $query->orderByDesc('tanggal_masuk')->orderByDesc('id'),
                            default => $query->orderBy('tanggal_expired')->orderBy('id'),
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
                    })
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $batch = BatchStok::find($state);
                        if ($batch) {
                            $set('batch_number', $batch->batch_number);
                            $set('tanggal_expired', $batch->tanggal_expired->format('Y-m-d'));
                            $set('stok_sistem', (int) $batch->jumlah);
                        }
                    }),
                TextInput::make('stok_sistem')
                    ->label('Stok Sistem')
                    ->readOnly()
                    ->extraInputAttributes([
                        'class' => 'text-right',
                    ]),
                TextInput::make('stok_fisik')
                    ->label('Stok Fisik')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        $stokSistem = (int) ($get('stok_sistem') ?? 0);
                        $stokFisik = (int) ($state ?? 0);
                        $set('selisih', $stokFisik - $stokSistem);
                    })
                    ->extraInputAttributes([
                        'class' => 'text-right',
                    ]),
                TextInput::make('selisih')
                    ->label('Selisih')
                    ->numeric()
                    ->default(0)
                    ->readOnly()
                    ->extraInputAttributes([
                        'class' => 'text-right',
                    ]),
            ],
            default => [
                Hidden::make('id'),
                Select::make('obat_id')
                    ->label('Obat')
                    ->required()
                    ->searchable()
                    ->live(onBlur: true)
                    ->options(fn (): array => Obat::orderBy('nama_obat')->pluck('nama_obat', 'id')->toArray())
                    ->afterStateUpdated(function ($state, Set $set, $get): void {
                        $stok = 0;

                        if ($state) {
                            $fasilitasId = $get('../../fasilitas_id');
                            if ($fasilitasId) {
                                $stok = StokFaskes::where('fasilitas_id', $fasilitasId)
                                    ->where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                            } else {
                                $stok = StokGudang::where('obat_id', $state)
                                    ->value('jumlah') ?? 0;
                            }
                        }

                        $set('batch_number', BatchNumberGenerator::generate((int) ($state ?? 0)));
                    }),
                TextInput::make('batch_number')
                    ->label('Nomor Batch')
                    ->maxLength(100)
                    ->default(fn ($get) => BatchNumberGenerator::generate((int) ($get('obat_id') ?? 0))),
                DatePicker::make('tanggal_expired')
                    ->label('Tanggal Expired'),
                TextInput::make('stok_fisik')
                    ->label('Stok Fisik')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->extraInputAttributes([
                        'class' => 'text-right',
                    ]),
            ],
        };
    }
}
