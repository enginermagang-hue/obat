<?php

namespace App\Filament\Resources\LaporanRkos\Schemas;

use App\Models\LaporanRko;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LaporanRkoForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;
        $isFaskesUser = filled($userFaskesId);
        $isSuperAdmin = $user?->hasRole('super_admin');
        $isAdminDinas = $user?->hasRole('admin_dinas');

        return $schema
            ->columns(4)
            ->components([
                TextInput::make('nomor_rko')
                    ->label('Nomor RKO')
                    ->required()
                    ->maxLength(100)
                    ->visible($isSuperAdmin),
                Hidden::make('nomor_rko')
                    ->default(fn () => 'RKO-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT)),
                Select::make('fasilitas_id')
                    ->label('Fasilitas Kesehatan')
                    ->relationship('fasilitas', 'nama')
                    ->searchable()
                    ->required()
                    ->default($isFaskesUser ? $userFaskesId : null)
                    ->disabled(! $isSuperAdmin)
                    ->dehydrated()
                    ->visible(fn () => $isSuperAdmin || $isAdminDinas),
                Hidden::make('fasilitas_id')
                    ->default($isFaskesUser ? $userFaskesId : null)
                    ->visible(fn () => $isFaskesUser && ! $isSuperAdmin),
                Select::make('periode_tahun')
                    ->label('Periode Tahun')
                    ->required()
                    ->options(array_combine(range(now()->year - 2, now()->year + 2), range(now()->year - 2, now()->year + 2)))
                    ->default((int) date('Y'))
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $schema): void {
                            $fasilitasId = $get('fasilitas_id');

                            if (blank($fasilitasId) || blank($value)) {
                                return;
                            }

                            $query = LaporanRko::where('fasilitas_id', $fasilitasId)
                                ->where('periode_tahun', (int) $value);

                            $record = $schema->getRecord();
                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail("RKO untuk tahun {$value} sudah ada. Silakan edit RKO yang sudah ada atau pilih tahun lain.");
                            }
                        },
                    ]),
                DatePicker::make('tanggal_pembuatan')
                    ->required()
                    ->default(now()),
                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->default('draft')
                    ->options(function () use ($isSuperAdmin, $isAdminDinas) {
                        if ($isSuperAdmin) {
                            return [
                                'draft' => 'Draft',
                                'diajukan' => 'Diajukan',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ];
                        }

                        if ($isAdminDinas) {
                            return [
                                'draft' => 'Draft',
                                'diajukan' => 'Diajukan',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ];
                        }

                        return [
                            'draft' => 'Draft',
                            'diajukan' => 'Ajukan',
                        ];
                    })
                    ->disabled(fn (Get $get) => ! $isSuperAdmin && $get('status') !== 'draft' && ! $isAdminDinas)
                    ->dehydrated(),
                DatePicker::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->nullable()
                    ->hidden(fn (Get $get) => ! in_array($get('status'), ['diajukan', 'disetujui', 'ditolak'])),
                DatePicker::make('tanggal_disetujui')
                    ->label('Tanggal Disetujui')
                    ->nullable()
                    ->hidden(fn (Get $get) => ! in_array($get('status'), ['disetujui', 'ditolak'])),
                Hidden::make('dibuat_oleh')
                    ->default(auth()->id()),
                TextInput::make('total_anggaran')
                    ->label('Total Anggaran')
                    ->disabled()
                    ->dehydrated()
                    ->live()
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                    // ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', '.'))
                    ->prefix('Rp'),
                Textarea::make('catatan')
                    ->columnStart(1)
                    ->columnSpanFull()
                    ->autosize()
                    ->nullable(),
                Section::make('Detail Item')
                    ->heading('Daftar Item RKO')
                    ->contained(false)
                    ->description('Tambahkan item obat untuk RKO ini.')
                    ->columnSpanFull()
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    public static function hitungAbcKategori(array $details): void
    {
        $items = [];
        foreach ($details as $index => $detail) {
            $items[$index] = (int) ($detail['rata_rata_pemakaian_bulanan'] ?? 0);
        }

        $total = array_sum($items);
        if ($total === 0) {
            return;
        }

        arsort($items);

        $cumulative = 0;
        foreach ($items as $index => $value) {
            $cumulative += $value;
            $pct = ($cumulative / $total) * 100;

            if ($pct <= 70) {
                $abc = 'A';
            } elseif ($pct <= 90) {
                $abc = 'B';
            } else {
                $abc = 'C';
            }

            $details[$index]['abc_kategori'] = $abc;
        }
    }
}
