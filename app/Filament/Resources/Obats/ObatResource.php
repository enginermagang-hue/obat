<?php

namespace App\Filament\Resources\Obats;

use App\Filament\Resources\Obats\Pages\ListObats;
use App\Filament\Resources\Obats\Schemas\ObatForm;
use App\Filament\Resources\Obats\Tables\ObatsTable;
use App\Models\Obat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ObatResource extends Resource
{
    protected static ?string $model = Obat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Obat';

    protected static ?string $pluralLabel = 'Obat';

    protected static ?string $slug = 'obat';

    protected static ?string $recordTitleAttribute = 'nama_obat';

    public static function form(Schema $schema): Schema
    {
        return ObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObatsTable::configure($table);
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
            'index' => ListObats::route('/'),
        ];
    }
}
