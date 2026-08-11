<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Stokobat\Boxicons\Boxicon;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('nama')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
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
                Textarea::make('alamat')
                    ->nullable(),
                Toggle::make('status')
                    ->live()
                    ->label(fn ($state) => $state ? 'Aktif' : 'Nonaktif')
                    ->aboveLabel('Status')
                    ->default(true)
                    ->dehydrateStateUsing(fn ($state) => $state ? 'aktif' : 'nonaktif')
                    ->onIcon(Boxicon::Check)
                    ->offIcon(Boxicon::X),
            ]);
    }
}
