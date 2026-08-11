<?php

namespace App\Filament\Resources\SumberDanas;

use App\Filament\Resources\SumberDanas\Pages\ListSumberDanas;
use App\Filament\Resources\SumberDanas\Schemas\SumberDanaForm;
use App\Filament\Resources\SumberDanas\Tables\SumberDanasTable;
use App\Models\SumberDana;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SumberDanaResource extends Resource
{
    protected static ?string $model = SumberDana::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Sumber Dana';

    protected static ?string $pluralLabel = 'Sumber Dana';

    protected static ?string $slug = 'sumber-dana';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SumberDanaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SumberDanasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSumberDanas::route('/'),
        ];
    }
}
