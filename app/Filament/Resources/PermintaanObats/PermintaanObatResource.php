<?php

namespace App\Filament\Resources\PermintaanObats;

use App\Filament\Resources\PermintaanObats\Pages\CreatePermintaanObat;
use App\Filament\Resources\PermintaanObats\Pages\DetailPermintaanObat;
use App\Filament\Resources\PermintaanObats\Pages\EditPermintaanObat;
use App\Filament\Resources\PermintaanObats\Pages\ListPermintaanObats;
use App\Filament\Resources\PermintaanObats\Schemas\PermintaanObatForm;
use App\Filament\Resources\PermintaanObats\Tables\PermintaanObatsTable;
use App\Models\PermintaanObat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PermintaanObatResource extends Resource
{
    protected static ?string $model = PermintaanObat::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string|UnitEnum|null $navigationGroup = 'Distribusi & Permintaan';

    protected static ?string $navigationLabel = 'Permintaan Obat';

    protected static ?string $pluralLabel = 'Permintaan Obat';

    protected static ?string $slug = 'permintaan-obat';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nomor_permintaan';

    public static function form(Schema $schema): Schema
    {
        return PermintaanObatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermintaanObatsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'menunggu_persetujuan')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // super_admin, admin_dinas, admin_gudang: hanya lihat permintaan dari puskesmas (ke dinas)
        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return $query->where('tipe_permintaan', 'puskesmas_ke_dinas');
        }

        $userFaskesId = $user->fasilitas_kesehatan_id;

        if (blank($userFaskesId)) {
            return $query->whereRaw('1 = 0');
        }

        $userFasilitas = $user->fasilitasKesehatan;

        // Puskesmas: lihat permintaan sendiri (puskesmas_ke_dinas sebagai pengirim)
        // dan permintaan dari pustu di bawahnya (pustu_ke_puskesmas sebagai tujuan)
        if ($userFasilitas && $userFasilitas->tipe === 'puskesmas') {
            $pustuIds = $userFasilitas->pustu()->pluck('fasilitas_kesehatan.id');

            return $query->where(function (Builder $q) use ($userFaskesId, $pustuIds) {
                $q->where('fasilitas_pengirim_id', $userFaskesId)
                    ->orWhere(function (Builder $subQ) use ($pustuIds) {
                        $subQ->where('tipe_permintaan', 'pustu_ke_puskesmas')
                            ->whereIn('fasilitas_pengirim_id', $pustuIds);
                    });
            });
        }

        // Pustu: hanya lihat permintaan miliknya sendiri (sebagai pengirim)
        return $query->where('tipe_permintaan', 'pustu_ke_puskesmas')
            ->where('fasilitas_pengirim_id', $userFaskesId);
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
            'index' => ListPermintaanObats::route('/'),
            'create' => CreatePermintaanObat::route('/create'),
            'view' => DetailPermintaanObat::route('/{record}'),
            'edit' => EditPermintaanObat::route('/{record}/edit'),
        ];
    }
}
