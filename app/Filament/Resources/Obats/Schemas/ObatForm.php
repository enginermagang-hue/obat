<?php

namespace App\Filament\Resources\Obats\Schemas;

use App\Enums\MetodeStok;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ObatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('ObatTabs')
                    ->tabs([
                        Tabs\Tab::make('Umum')
                            ->schema([
                                TextInput::make('kode_obat')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Kode unik obat (contoh: 001)'),
                                TextInput::make('nama_obat')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Nama dagang/obat'),
                                TextInput::make('nama_generik')
                                    ->maxLength(255)
                                    ->helperText('Nama generik/bahan obat'),
                                Grid::make(2)
                                    ->components([

                                        TextInput::make('kategori')
                                            ->label('Kategori')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Kategori obat (contoh: Antibiotik, Analgesik)'),
                                        TextInput::make('satuan')
                                            ->label('Satuan')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Satuan pengukuran (contoh: tablet, botol)'),
                                    ]),
                                TextInput::make('kekuatan')
                                    ->maxLength(255)
                                    ->helperText('Kekuatan dosis (contoh: 500mg, 10ml)'),
                                Select::make('bentuk_sediaan')
                                    ->required()
                                    ->options([
                                        'tablet' => 'Tablet',
                                        'kapsul' => 'Kapsul',
                                        'sirup' => 'Sirup',
                                        'salep' => 'Salep',
                                        'injeksi' => 'Injeksi',
                                        'drop' => 'Drop',
                                        'inhaler' => 'Inhaler',
                                        'suppositoria' => 'Suppositoria',
                                    ])
                                    ->helperText('Bentuk sediaan obat'),
                            ]),
                        Tabs\Tab::make('Lanjutan')
                            ->schema([
                                TextInput::make('produsen')
                                    ->maxLength(255)
                                    ->helperText('Nama produsen/pabrik obat'),
                                TextInput::make('kemasan')
                                    ->maxLength(255)
                                    ->helperText('Kemasan/volume (contoh: strip 10 tablet)'),
                                TextInput::make('harga_satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->helperText('Harga per satuan (Rp)'),
                                Select::make('ven_kategori')
                                    ->label('VEN Kategori')
                                    ->options([
                                        'V' => 'Vital',
                                        'E' => 'Esensial',
                                        'N' => 'Non-Esensial',
                                    ])
                                    ->helperText('V=life saving, E=esensial, N=penunjang'),
                                Select::make('status')
                                    ->required()
                                    ->default('aktif')
                                    ->options([
                                        'aktif' => 'Aktif',
                                        'nonaktif' => 'Nonaktif',
                                    ])
                                    ->helperText('Status obat di sistem'),
                                Select::make('metode_stok')
                                    ->label('Metode Stok')
                                    ->required()
                                    ->default('fefo')
                                    ->options(collect(MetodeStok::cases())
                                        ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
                                        ->toArray())
                                    ->helperText('Metode pemilihan batch stok untuk transaksi'),
                            ]),
                    ]),
            ]);
    }
}
