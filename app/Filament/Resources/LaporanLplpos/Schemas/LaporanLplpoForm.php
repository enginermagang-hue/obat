<?php

namespace App\Filament\Resources\LaporanLplpos\Schemas;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LaporanLplpoForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $userFaskesId = $user?->fasilitas_kesehatan_id;
        $isFaskesUser = filled($userFaskesId);
        $isSuperAdmin = $user?->hasRole('super_admin');
        $isAdminDinas = $user?->hasRole('admin_dinas');

        return $schema
            ->columns(3)
            ->components([
                TextInput::make('nomor_laporan')
                    ->label('Nomor Laporan')
                    ->required()
                    ->maxLength(100)
                    ->visible($isSuperAdmin),
                Hidden::make('nomor_laporan')
                    ->default(fn () => 'LPLPO-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT)),
                Select::make('fasilitas_id')
                    ->label('Fasilitas Kesehatan')
                    ->options(fn (): array => FasilitasKesehatan::pluck('nama', 'id')->all())
                    ->required()
                    ->default($isFaskesUser ? $userFaskesId : null)
                    ->disabled(! $isSuperAdmin)
                    ->dehydrated()
                    ->visible(fn () => $isSuperAdmin || $isAdminDinas),
                Hidden::make('fasilitas_id')
                    ->default($isFaskesUser ? $userFaskesId : null)
                    ->visible(fn () => $isFaskesUser && ! $isSuperAdmin),
                Select::make('periode_bulan')
                    ->label('Periode Bulan')
                    ->required()
                    ->options(array_combine(range(1, 12), [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
                    ]))
                    ->default((int) date('n'))
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get, $schema): void {
                            $fasilitasId = $get('fasilitas_id');
                            $tahun = $get('periode_tahun');

                            if (blank($fasilitasId) || blank($tahun)) {
                                return;
                            }

                            $query = LaporanLplpo::where('fasilitas_id', $fasilitasId)
                                ->where('periode_bulan', $value)
                                ->where('periode_tahun', $tahun);

                            $record = $schema->getRecord();
                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('LPLPO untuk periode ini sudah ada. Silakan pilih periode lain.');
                            }
                        },
                    ]),
                Select::make('periode_tahun')
                    ->label('Periode Tahun')
                    ->required()
                    ->options(fn () => array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))
                    ->default((int) date('Y')),
                DatePicker::make('tanggal_pembuatan')
                    ->required()
                    ->default(now()),
                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->default('draft')
                    ->options([
                        'draft' => 'Draft',
                        'selesai' => 'Selesai',
                    ])
                    ->disabled(fn (Get $get) => ! $isSuperAdmin && $get('status') !== 'draft')
                    ->dehydrated()
                    ->hidden(fn (?LaporanLplpo $record) => $record === null),
                Hidden::make('status')
                    ->default('draft')
                    ->visible(fn (?LaporanLplpo $record) => $record === null),
                Hidden::make('dibuat_oleh')
                    ->default(auth()->id()),
                Hidden::make('parent_lplpo_id')
                    ->nullable(),
                Textarea::make('catatan')
                    ->columnSpanFull()
                    ->autosize()
                    ->nullable(),
                Section::make('Detail Obat')
                    ->heading('Daftar Item LPLPO')
                    ->contained(false)
                    ->description('Tambahkan item obat untuk laporan ini.')
                    ->columnSpanFull()
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }
}
