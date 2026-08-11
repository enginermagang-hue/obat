<?php

namespace App\Filament\Resources\NeracaTahunans;

use App\Filament\Resources\NeracaTahunans\Pages\CreateNeracaTahunan;
use App\Filament\Resources\NeracaTahunans\Pages\EditNeracaTahunan;
use App\Filament\Resources\NeracaTahunans\Pages\ListNeracaTahunans;
use App\Filament\Resources\NeracaTahunans\Pages\ViewNeracaTahunan;
use App\Filament\Resources\NeracaTahunans\Schemas\NeracaTahunanForm;
use App\Filament\Resources\NeracaTahunans\Tables\NeracaTahunansTable;
use App\Models\NeracaTahunan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NeracaTahunanResource extends Resource
{
    protected static ?string $model = NeracaTahunan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Neraca Tahunan';

    protected static ?string $pluralLabel = 'Neraca Tahunan';

    protected static ?string $slug = 'neraca-tahunan';

    protected static ?string $recordTitleAttribute = 'nomor_neraca';

    public static function form(Schema $schema): Schema
    {
        return NeracaTahunanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NeracaTahunansTable::configure($table);
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
            'index' => ListNeracaTahunans::route('/'),
            'create' => CreateNeracaTahunan::route('/create'),
            'view' => ViewNeracaTahunan::route('/{record}'),
            'edit' => EditNeracaTahunan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // super_admin & admin_gudang: all records
        if ($user->hasRole('super_admin') || $user->hasRole('admin_gudang')) {
            return $query;
        }

        // admin_dinas: all records (no tipe_pengajuan filter needed)
        if ($user->hasRole('admin_dinas')) {
            return $query;
        }

        // user faskes
        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId)) {
            $faskes = $user->fasilitasKesehatan;

            if ($faskes && $faskes->tipe === 'puskesmas') {
                $pustuIds = $faskes->pustu()->pluck('id')->toArray();
                $faskesIds = array_merge([$userFaskesId], $pustuIds);

                return $query->whereIn('fasilitas_id', $faskesIds);
            }

            return $query->where('fasilitas_id', $userFaskesId);
        }

        return $query->whereRaw('1 = 0');
    }
}
