<?php

namespace App\Filament\Resources\FasilitasKesehatans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class FasilitasKesehatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('FaskesTabs')
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make('Utama')
                            ->schema([
                                TextInput::make('kode_faskes')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('tipe')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->options([
                                        'puskesmas' => 'Puskesmas',
                                        'pustu' => 'Pustu',
                                    ]),
                                Select::make('puskesmas_induk_id')
                                    ->label('Puskesmas Induk')
                                    ->relationship('puskesmasInduk', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->hidden(fn (mixed $get): bool => $get('tipe') !== 'pustu'),
                                Select::make('status')
                                    ->required()
                                    ->native(false)
                                    ->default('aktif')
                                    ->options([
                                        'aktif' => 'Aktif',
                                        'nonaktif' => 'Nonaktif',
                                    ]),
                            ]),
                        Tabs\Tab::make('Informasi Kontak')
                            ->schema([
                                TextInput::make('pic')
                                    ->label('PIC')
                                    ->maxLength(255),
                                TextInput::make('kontak_pic')
                                    ->label('Kontak PIC')
                                    ->maxLength(255),
                                TextInput::make('telepon')
                                    ->tel()
                                    ->maxLength(255),
                                TextInput::make('kepala_faskes')
                                    ->maxLength(255),
                                Textarea::make('alamat'),
                            ]),
                    ]),
            ]);
    }
}
