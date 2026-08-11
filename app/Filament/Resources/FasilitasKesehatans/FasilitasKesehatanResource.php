<?php

namespace App\Filament\Resources\FasilitasKesehatans;

use App\Filament\Resources\FasilitasKesehatans\Pages\ListFasilitasKesehatans;
use App\Filament\Resources\FasilitasKesehatans\Schemas\FasilitasKesehatanForm;
use App\Filament\Resources\FasilitasKesehatans\Tables\FasilitasKesehatansTable;
use App\Models\FasilitasKesehatan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FasilitasKesehatanResource extends Resource
{
    protected static ?string $model = FasilitasKesehatan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Faskes';

    protected static ?string $pluralLabel = 'Faskes';

    protected static ?string $slug = 'faskes';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return FasilitasKesehatanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FasilitasKesehatansTable::configure($table);
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
            'index' => ListFasilitasKesehatans::route('/'),
        ];
    }
}
