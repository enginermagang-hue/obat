<?php

namespace App\Filament\Resources\PrediksiKebutuhans;

use App\Filament\Resources\PrediksiKebutuhans\Pages\ListPrediksiKebutuhans;
use App\Filament\Resources\PrediksiKebutuhans\Schemas\PrediksiKebutuhanForm;
use App\Filament\Resources\PrediksiKebutuhans\Tables\PrediksiKebutuhansTable;
use App\Models\PrediksiKebutuhan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PrediksiKebutuhanResource extends Resource
{
    protected static ?string $model = PrediksiKebutuhan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Ai Service';

    protected static ?string $navigationLabel = 'Hasil Prediksi';

    protected static ?string $pluralLabel = 'Hasil Prediksi';

    protected static ?string $slug = 'prediksi-kebutuhan';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin_dinas']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return PrediksiKebutuhanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrediksiKebutuhansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrediksiKebutuhans::route('/'),
        ];
    }
}
