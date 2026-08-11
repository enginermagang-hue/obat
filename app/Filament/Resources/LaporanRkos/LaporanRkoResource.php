<?php

namespace App\Filament\Resources\LaporanRkos;

use App\Filament\Resources\LaporanRkos\Pages\CreateLaporanRko;
use App\Filament\Resources\LaporanRkos\Pages\EditLaporanRko;
use App\Filament\Resources\LaporanRkos\Pages\ListLaporanRkos;
use App\Filament\Resources\LaporanRkos\Pages\ViewLaporanRko;
use App\Filament\Resources\LaporanRkos\Schemas\LaporanRkoForm;
use App\Filament\Resources\LaporanRkos\Tables\LaporanRkosTable;
use App\Models\FasilitasKesehatan;
use App\Models\LaporanRko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LaporanRkoResource extends Resource
{
    protected static ?string $model = LaporanRko::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'RKO';

    protected static ?string $pluralLabel = 'RKO';

    protected static ?string $slug = 'rko';

    protected static ?string $recordTitleAttribute = 'nomor_rko';

    public static function form(Schema $schema): Schema
    {
        return LaporanRkoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaporanRkosTable::configure($table);
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
            'index' => ListLaporanRkos::route('/'),
            'create' => CreateLaporanRko::route('/create'),
            'view' => ViewLaporanRko::route('/{record}'),
            'edit' => EditLaporanRko::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // super_admin, admin_gudang, admin_dinas: all records
        if ($user->hasRole('super_admin') || $user->hasRole('admin_gudang') || $user->hasRole('admin_dinas')) {
            return $query;
        }

        // user faskes: own records + pustu records (if puskesmas)
        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (filled($userFaskesId)) {
            $faskesIds = [$userFaskesId];
            $faskes = FasilitasKesehatan::find($userFaskesId);

            if ($faskes?->tipe === 'puskesmas') {
                $faskesIds = array_merge($faskesIds, $faskes->pustu()->pluck('id')->toArray());
            }

            return $query->whereIn('fasilitas_id', $faskesIds);
        }

        return $query->whereRaw('1 = 0');
    }
}
