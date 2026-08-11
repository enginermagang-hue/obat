<?php

namespace App\Filament\Resources\StokGudangs;

use App\Filament\Resources\StokGudangs\Pages\ListStokGudangs;
use App\Filament\Resources\StokGudangs\Pages\RiwayatStokObat;
use App\Filament\Resources\StokGudangs\Tables\StokGudangsTable;
use App\Models\StokGudang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class StokGudangResource extends Resource
{
    protected static ?string $model = StokGudang::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stok Gudang';

    protected static ?string $pluralLabel = 'Stok Gudang';

    protected static ?string $slug = 'stok-gudang';

    protected static ?string $recordTitleAttribute = 'obat.nama_obat';

    public static function table(Table $table): Table
    {
        return StokGudangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStokGudangs::route('/'),
            'riwayat-obat' => RiwayatStokObat::route('/{obat_id}/riwayat'),
        ];
    }
}
