<?php

namespace App\Filament\Resources\InspeksiReturs;

use App\Filament\Resources\InspeksiReturs\Pages\CreateInspeksiRetur;
use App\Filament\Resources\InspeksiReturs\Pages\EditInspeksiRetur;
use App\Filament\Resources\InspeksiReturs\Pages\ListInspeksiReturs;
use App\Filament\Resources\InspeksiReturs\Schemas\InspeksiReturForm;
use App\Filament\Resources\InspeksiReturs\Tables\InspeksiRetursTable;
use App\Models\InspeksiRetur;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InspeksiReturResource extends Resource
{
    protected static ?string $model = InspeksiRetur::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Inspeksi Retur';

    protected static ?string $modelLabel = 'Inspeksi Retur';

    protected static ?string $pluralModelLabel = 'Inspeksi Retur';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InspeksiReturForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InspeksiRetursTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInspeksiReturs::route('/'),
            'create' => CreateInspeksiRetur::route('/create'),
            'edit' => EditInspeksiRetur::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
