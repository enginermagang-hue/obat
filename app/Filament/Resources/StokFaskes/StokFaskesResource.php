<?php

namespace App\Filament\Resources\StokFaskes;

use App\Filament\Resources\StokFaskes\Pages\DaftarBatchStokFaskes;
use App\Filament\Resources\StokFaskes\Pages\ListStokFaskes;
use App\Filament\Resources\StokFaskes\Pages\RiwayatStokFaskes;
use App\Filament\Resources\StokFaskes\Tables\StokFaskesTable;
use App\Models\StokFaskes;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class StokFaskesResource extends Resource
{
    protected static ?string $model = StokFaskes::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stok Faskes';

    protected static ?string $pluralLabel = 'Stok Faskes';

    protected static ?string $slug = 'stok-faskes';

    protected static ?string $recordTitleAttribute = 'obat.nama_obat';

    public static function table(Table $table): Table
    {
        return StokFaskesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Role dengan fasilitas (puskesmas/pustu): hanya lihat stok faskes miliknya sendiri
        if (filled($user->fasilitas_kesehatan_id)) {
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
            'index' => ListStokFaskes::route('/'),
            'batch' => DaftarBatchStokFaskes::route('/{obat_id}/batch'),
            'riwayat-stok' => RiwayatStokFaskes::route('/{obat_id}/riwayat'),
        ];
    }
}
