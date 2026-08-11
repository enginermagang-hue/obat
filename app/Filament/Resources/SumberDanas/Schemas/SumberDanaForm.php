<?php

namespace App\Filament\Resources\SumberDanas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SumberDanaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(2)
                    ->components([
                        TextInput::make('kode')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Select::make('tahun')
                            ->label('Tahun')
                            ->required()
                            ->options(array_combine(
                                range(now()->year - 2, now()->year + 2),
                                range(now()->year - 2, now()->year + 2),
                            ))
                            ->default((int) now()->format('Y')),
                    ]),
                TextInput::make('nama')
                    ->label('Nama Sumber Dana')
                    ->required()
                    ->maxLength(255),
                TextInput::make('total_anggaran')
                    ->label('Total Anggaran (Pagu)')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                    ->required(),
                TextInput::make('keterangan')
                    ->label('Keterangan')
                    ->maxLength(255)
                    ->nullable(),

                Toggle::make('status')
                    ->label('Aktif')
                    ->default(true)
                    ->onIcon('heroicon-o-check')
                    ->offIcon('heroicon-o-x-mark')
                    ->onColor('success')
                    ->offColor('danger'),
            ]);
    }
}
