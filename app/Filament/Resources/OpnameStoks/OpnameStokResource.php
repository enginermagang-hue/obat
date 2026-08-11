<?php

namespace App\Filament\Resources\OpnameStoks;

use App\Filament\Resources\OpnameStoks\Pages\ListOpnameStoks;
use App\Filament\Resources\OpnameStoks\Pages\ViewOpnameStok;
use App\Filament\Resources\OpnameStoks\Schemas\OpnameStokForm;
use App\Filament\Resources\OpnameStoks\Tables\OpnameStoksTable;
use App\Models\OpnameStok;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class OpnameStokResource extends Resource
{
    protected static ?string $model = OpnameStok::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stok Opname';

    protected static ?string $pluralLabel = 'Stok Opname';

    protected static ?string $slug = 'stok-opname';

    protected static ?string $recordTitleAttribute = 'nomor_opname';

    public static function form(Schema $schema): Schema
    {
        return OpnameStokForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpnameStoksTable::configure($table);
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
            'index' => ListOpnameStoks::route('/'),
            'view' => ViewOpnameStok::route('/{record}'),
        ];
    }
}
