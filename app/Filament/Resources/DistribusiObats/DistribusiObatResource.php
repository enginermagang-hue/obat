<?php

namespace App\Filament\Resources\DistribusiObats;

use App\Filament\Resources\DistribusiObats\Pages\CreateDistribusiObat;
use App\Filament\Resources\DistribusiObats\Pages\DetailDistribusi;
use App\Filament\Resources\DistribusiObats\Pages\EditDistribusiObat;
use App\Filament\Resources\DistribusiObats\Pages\ListDistribusiObats;
use App\Filament\Resources\DistribusiObats\Schemas\DistribusiObatForm;
use App\Filament\Resources\DistribusiObats\Tables\DistribusiObatsTable;
use App\Models\DistribusiObat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DistribusiObatResource extends Resource
{
    protected static ?string $model = DistribusiObat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Distribusi & Permintaan';

    protected static ?string $navigationLabel = 'Distribusi Obat';

    protected static ?string $pluralLabel = 'Distribusi Obat';

    protected static ?string $slug = 'distribusi-obat';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nomor_surat_jalan';

    public static function form(Schema $schema): Schema
    {
        return DistribusiObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistribusiObatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
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

        // pustu: hanya distribusi yang ditujukan ke faskesnya (sesuai policy view)
        if ($user->hasRole('pustu')) {
            return $query->where('fasilitas_penerima_id', $userFaskesId);
        }

        // puskesmas & role faskes lainnya: pengirim ATAU penerima
        return $query->where(function (Builder $q) use ($userFaskesId) {
            $q->where('fasilitas_pengirim_id', $userFaskesId)
                ->orWhere('fasilitas_penerima_id', $userFaskesId);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'dalam_pengiriman')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistribusiObats::route('/'),
            'create' => CreateDistribusiObat::route('/create'),
            'view' => DetailDistribusi::route('/{record}'),
            'edit' => EditDistribusiObat::route('/{record}/edit'),
        ];
    }
}
