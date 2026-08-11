<?php

namespace App\Filament\Resources\LaporanLplpos;

use App\Filament\Resources\LaporanLplpos\Pages\CreateLaporanLplpo;
use App\Filament\Resources\LaporanLplpos\Pages\EditLaporanLplpo;
use App\Filament\Resources\LaporanLplpos\Pages\ListLaporanLplpos;
use App\Filament\Resources\LaporanLplpos\Pages\ShowLaporanLplpo;
use App\Filament\Resources\LaporanLplpos\Schemas\LaporanLplpoForm;
use App\Filament\Resources\LaporanLplpos\Tables\LaporanLplposTable;
use App\Models\LaporanLplpo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LaporanLplpoResource extends Resource
{
    protected static ?string $model = LaporanLplpo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'LPLPO';

    protected static ?string $pluralLabel = 'LPLPO';

    protected static ?string $slug = 'lplpo';

    protected static ?string $recordTitleAttribute = 'nomor_laporan';

    public static function form(Schema $schema): Schema
    {
        return LaporanLplpoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaporanLplposTable::configure($table);
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
            'index' => ListLaporanLplpos::route('/'),
            'create' => CreateLaporanLplpo::route('/create'),
            'show' => ShowLaporanLplpo::route('/{record}'),
            'edit' => EditLaporanLplpo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Default filter: only show current (non-revised) LPLPO
        $query->whereNull('parent_lplpo_id');

        // super_admin & admin_gudang & admin_dinas: all records
        if ($user->hasAnyRole(['super_admin', 'admin_gudang', 'admin_dinas'])) {
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
