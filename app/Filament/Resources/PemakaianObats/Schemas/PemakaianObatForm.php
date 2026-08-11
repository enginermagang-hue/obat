<?php

namespace App\Filament\Resources\PemakaianObats\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PemakaianObatForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('super_admin');

        return $schema
            ->columns(1)
            ->components([
                // ═══════════════════════════════════════════
                // Section: Informasi Pelayanan
                // ═══════════════════════════════════════════
                Section::make('Informasi Pelayanan')
                    ->heading('')
                    ->contained(false)
                    ->schema([
                        // Select::make('fasilitas_id')
                        //     ->label('Fasilitas')
                        //     ->relationship('fasilitas', 'nama')
                        //     ->searchable()
                        //     ->preload()
                        //     ->required()
                        //     ->default($user?->fasilitas_kesehatan_id)
                        //     ->disabled(! $isSuperAdmin)
                        //     ->dehydrated()
                        //     ->helperText($isSuperAdmin
                        //         ? 'Pilih fasilitas (khusus super admin)'
                        //         : 'Otomatis terisi dari akun Anda')
                        //     ->columnSpan(1),

                        Hidden::make('fasilitas_id')
                            ->default($user?->fasilitas_kesehatan_id),

                        DatePicker::make('tanggal_pemakaian')
                            ->label('Tanggal Pemakaian')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Select::make('jenis_pelayanan')
                            ->label('Jenis Pelayanan')
                            ->required()
                            ->options([
                                'rawat_jalan' => 'Rawat Jalan',
                                'rawat_inap' => 'Rawat Inap',
                                'uks' => 'UKS',
                                'posyandu' => 'Posyandu',
                                'pusling' => 'Pusling (Puskesmas Keliling)',
                                'gigi' => 'Poli Gigi',
                                'laboratorium' => 'Laboratorium',
                                'apotek' => 'Apotek',
                                'lainnya' => 'Lainnya',
                            ])
                            ->native(false)
                            ->columnSpan(1),

                        Hidden::make('user_id')
                            ->default($user?->id),
                    ])
                    ->columns(4),

                // ═══════════════════════════════════════════
                // Section: Data Pasien & Catatan
                // ═══════════════════════════════════════════
                Section::make('Data Pasien')
                    ->heading('')
                    ->contained(false)
                    ->schema([
                        TextInput::make('nama_pasien')
                            ->label('Nama Pasien')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('Contoh: Budi Santoso')
                            ->helperText('Nama pasien. Kosongkan untuk pemakaian non-pasien.'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(2)
                            ->nullable()
                            ->placeholder('Tambahkan catatan jika diperlukan...'),
                    ])
                    ->columns(2),

                // ═══════════════════════════════════════════
                // Section: Detail Obat (Embedded Table)
                // ═══════════════════════════════════════════
                Section::make('Detail Obat')
                    ->heading('Daftar Obat yang Dipakai')
                    ->contained(false)
                    ->description('Tambahkan minimal 1 item obat. Stok akan berkurang otomatis saat disimpan.')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }
}
