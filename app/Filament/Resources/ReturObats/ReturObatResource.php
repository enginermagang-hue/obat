<?php

namespace App\Filament\Resources\ReturObats;

use App\Filament\Resources\ReturObats\Pages\CreateReturObat;
use App\Filament\Resources\ReturObats\Pages\EditReturObat;
use App\Filament\Resources\ReturObats\Pages\ListReturObats;
use App\Filament\Resources\ReturObats\Pages\ViewReturObat;
use App\Filament\Resources\ReturObats\Schemas\ReturObatForm;
use App\Filament\Resources\ReturObats\Tables\ReturObatsTable;
use App\Models\ReturObat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReturObatResource extends Resource
{
    protected static ?string $model = ReturObat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|UnitEnum|null $navigationGroup = 'Distribusi & Permintaan';

    protected static ?string $navigationLabel = 'Retur Obat';

    protected static ?string $pluralLabel = 'Retur Obat';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'retur-obat';

    protected static ?string $recordTitleAttribute = 'nomor_retur';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(['super_admin', 'admin_dinas', 'admin_gudang'])) {
            return $query;
        }

        $faskesId = $user->fasilitas_kesehatan_id;

        if ($user->hasRole('puskesmas') && filled($faskesId)) {
            return $query->where(function (Builder $q) use ($faskesId) {
                $q->where(fn (Builder $qq) => $qq->where('tipe_retur', 'pustu_ke_puskesmas')->where('fasilitas_penerima_id', $faskesId))
                    ->orWhere(fn (Builder $qq) => $qq->where('tipe_retur', 'puskesmas_ke_gudang')->where('fasilitas_pengirim_id', $faskesId));
            });
        }

        if ($user->hasRole('pustu') && filled($faskesId)) {
            return $query->where('tipe_retur', 'pustu_ke_puskesmas')
                ->where('fasilitas_pengirim_id', $faskesId);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return ReturObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturObatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturObats::route('/'),
            'create' => CreateReturObat::route('/create'),
            'view' => ViewReturObat::route('/{record}'),
            'edit' => EditReturObat::route('/{record}/edit'),
        ];
    }
}
