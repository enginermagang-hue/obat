<?php

namespace App\Filament\Resources\RiwayatStoks;

use App\Filament\Resources\RiwayatStoks\Pages\ListRiwayatStoks;
use App\Filament\Resources\RiwayatStoks\Tables\RiwayatStoksTable;
use App\Models\RiwayatStok;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class RiwayatStokResource extends Resource
{
    protected static ?string $model = RiwayatStok::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Riwayat Stok';

    protected static ?string $pluralLabel = 'Riwayat Stok';

    protected static ?string $slug = 'riwayat-stok';

    protected static ?string $recordTitleAttribute = 'obat.nama_obat';

    public static function table(Table $table): Table
    {
        return RiwayatStoksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiwayatStoks::route('/'),
        ];
    }
}
