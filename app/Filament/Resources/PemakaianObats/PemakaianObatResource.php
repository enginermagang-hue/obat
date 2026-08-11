<?php

namespace App\Filament\Resources\PemakaianObats;

use App\Filament\Resources\PemakaianObats\Pages\CreatePemakaianObat;
use App\Filament\Resources\PemakaianObats\Pages\EditPemakaianObat;
use App\Filament\Resources\PemakaianObats\Pages\ListPemakaianObats;
use App\Filament\Resources\PemakaianObats\Pages\ViewPemakaianObat;
use App\Filament\Resources\PemakaianObats\Schemas\PemakaianObatForm;
use App\Filament\Resources\PemakaianObats\Tables\PemakaianObatsTable;
use App\Models\PemakaianObat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PemakaianObatResource extends Resource
{
    protected static ?string $model = PemakaianObat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Distribusi & Permintaan';

    protected static ?string $navigationLabel = 'Pemakaian Obat';

    protected static ?string $pluralLabel = 'Pemakaian Obat';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'pemakaian-obat';

    protected static ?string $recordTitleAttribute = 'nomor_pemakaian';

    public static function form(Schema $schema): Schema
    {
        return PemakaianObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PemakaianObatsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['fasilitas', 'user']);

        $user = Auth::user();

        // Role dengan fasilitas (puskesmas/pustu) hanya lihat pemakaian milik faskesnya
        if (filled($user?->fasilitas_kesehatan_id)) {
            $query->where('fasilitas_id', $user->fasilitas_kesehatan_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPemakaianObats::route('/'),
            'create' => CreatePemakaianObat::route('/create'),
            'view' => ViewPemakaianObat::route('/{record}'),
            'edit' => EditPemakaianObat::route('/{record}/edit'),
        ];
    }
}
