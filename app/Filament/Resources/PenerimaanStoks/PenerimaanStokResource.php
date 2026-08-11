<?php

namespace App\Filament\Resources\PenerimaanStoks;

use App\Filament\Resources\PenerimaanStoks\Pages\CreatePenerimaanStok;
use App\Filament\Resources\PenerimaanStoks\Pages\EditPenerimaanStok;
use App\Filament\Resources\PenerimaanStoks\Pages\ListPenerimaanStoks;
use App\Filament\Resources\PenerimaanStoks\Pages\ViewPenerimaanStok;
use App\Filament\Resources\PenerimaanStoks\Schemas\PenerimaanStokForm;
use App\Filament\Resources\PenerimaanStoks\Tables\PenerimaanStoksTable;
use App\Models\PenerimaanStok;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PenerimaanStokResource extends Resource
{
    protected static ?string $model = PenerimaanStok::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Distribusi & Permintaan';

    protected static ?string $navigationLabel = 'Penerimaan Stok';

    protected static ?string $pluralLabel = 'Penerimaan Stok';

    protected static ?string $slug = 'penerimaan-stok';

    protected static ?string $recordTitleAttribute = 'nomor_penerimaan';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PenerimaanStokForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenerimaanStoksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // super_admin, admin_dinas, admin_gudang: semua data
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return $query;
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return $query->whereRaw('1 = 0');
        }

        // puskesmas & pustu: hanya penerimaan untuk faskesnya
        return $query->where('fasilitas_id', $userFaskesId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenerimaanStoks::route('/'),
            'create' => CreatePenerimaanStok::route('/create'),
            'view' => ViewPenerimaanStok::route('/{record}'),
            'edit' => EditPenerimaanStok::route('/{record}/edit'),
        ];
    }
}
