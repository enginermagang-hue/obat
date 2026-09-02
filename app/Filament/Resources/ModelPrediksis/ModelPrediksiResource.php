<?php

namespace App\Filament\Resources\ModelPrediksis;

use App\Filament\Resources\ModelPrediksis\Pages\ListModelPrediksis;
use App\Filament\Resources\ModelPrediksis\Schemas\ModelPrediksiForm;
use App\Filament\Resources\ModelPrediksis\Tables\ModelPrediksisTable;
use App\Models\ModelPrediksi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ModelPrediksiResource extends Resource
{
    protected static ?string $model = ModelPrediksi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Ai Service';

    protected static ?string $navigationLabel = 'Model Prediksi';

    protected static ?string $pluralLabel = 'Model Prediksi';

    protected static ?string $slug = 'model-prediksi';

    protected static ?string $recordTitleAttribute = null;

    protected static ?int $navigationSort = 3;

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
        return ModelPrediksiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModelPrediksisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModelPrediksis::route('/'),
        ];
    }
}
