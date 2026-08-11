<?php

namespace App\Filament\Resources\DistribusiObats\Schemas;

use App\Filament\Resources\DistribusiObats\Pages\CreateDistribusiObat;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use Filament\Forms\Components as FormComponents;
use Filament\Schemas\Components as ScehmaComponents;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DistribusiObatForm
{
    public static function configure(Schema $schema): Schema
    {

        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;
        $isSuperAdmin = $user?->hasRole('super_admin');
        $isFaskesUser = filled($userFaskes);

        $senderFacilityId = match (true) {
            $isSuperAdmin => null,
            $isFaskesUser => $userFaskes?->id,
            default => null,
        };

        return $schema
            ->columns(1)
            ->components([
                FormComponents\Hidden::make('fasilitas_pengirim_id')
                    ->default($senderFacilityId),

                ScehmaComponents\Grid::make([
                    'xl' => '4',
                    '2xl' => '5',
                ])
                    ->components([
                        FormComponents\TextInput::make('nomor_surat_jalan')
                            ->label('Nomor Surat Jalan')
                            ->required()
                            ->maxLength(100)
                            ->disabled(! $isSuperAdmin)
                            ->default(function () {
                                $year = date('Y');
                                $prefix = "SJ/{$year}/";
                                $lastNumber = DistribusiObat::query()
                                    ->where('nomor_surat_jalan', 'like', "{$prefix}%")
                                    ->orderBy('id', 'desc')
                                    ->value('nomor_surat_jalan');
                                if ($lastNumber) {
                                    $lastSeq = (int) substr($lastNumber, strrpos($lastNumber, '/') + 1);
                                    $nextSeq = $lastSeq + 1;
                                } else {
                                    $nextSeq = 1;
                                }

                                return $prefix.str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
                            }),

                        FormComponents\DatePicker::make('tanggal_kirim')
                            ->label('Tanggal Kirim')
                            ->required()
                            ->default(now())
                            ->native(false),

                        FormComponents\Select::make('permintaan_id')
                            ->label('Permintaan Terkait')
                            ->relationship('permintaan', 'nomor_permintaan', fn ($query) => $query->whereIn('status', ['disetujui']))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $livewire) {
                                if (! $livewire instanceof CreateDistribusiObat) {
                                    return;
                                }

                                $livewire->loadPermintaanItems(blank($state) ? null : (int) $state);
                            }),

                        FormComponents\Select::make('fasilitas_penerima_id')
                            ->label('Penerima')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->options(function () use ($isFaskesUser, $isSuperAdmin, $userFaskes) {
                                if ($isSuperAdmin) {
                                    return FasilitasKesehatan::query()
                                        ->where('tipe', 'puskesmas')
                                        ->where('status', 'aktif')
                                        ->pluck('nama', 'id');
                                } elseif ($isFaskesUser && $userFaskes->tipe === 'puskesmas') {
                                    return FasilitasKesehatan::query()
                                        ->where('tipe', 'pustu')
                                        ->where('puskesmas_induk_id', $userFaskes->id)
                                        ->where('status', 'aktif')
                                        ->pluck('nama', 'id');
                                } elseif (! $isFaskesUser) {
                                    return FasilitasKesehatan::query()
                                        ->where('tipe', 'puskesmas')
                                        ->where('status', 'aktif')
                                        ->pluck('nama', 'id');
                                }
                            }),
                    ]),

                ScehmaComponents\Section::make('Detail Obat')
                    ->heading('Daftar Item')
                    ->contained(false)
                    ->schema([
                        ScehmaComponents\EmbeddedTable::make(),
                    ]),
            ]);
    }

    public static function getSenderFacilityId(mixed $livewire = null): ?int
    {
        $user = Auth::user();
        $userFaskes = $user?->fasilitasKesehatan;
        $isSuperAdmin = $user?->hasRole('super_admin');

        if (! $isSuperAdmin && filled($userFaskes)) {
            return $userFaskes->id;
        }

        if ($isSuperAdmin) {
            if ($livewire) {
                $fasilitasId = data_get($livewire, 'data.fasilitas_pengirim_id');
                if (filled($fasilitasId)) {
                    return (int) $fasilitasId;
                }
            }

            return null;
        }

        return null;
    }
}
