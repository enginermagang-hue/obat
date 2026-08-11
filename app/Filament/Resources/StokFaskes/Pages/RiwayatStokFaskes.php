<?php

namespace App\Filament\Resources\StokFaskes\Pages;

use App\Filament\Resources\RiwayatStoks\Tables\RiwayatStoksTable;
use App\Filament\Resources\StokFaskes\StokFaskesResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\RiwayatStok;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RiwayatStokFaskes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StokFaskesResource::class;

    public ?int $obatId = null;

    public ?int $fasilitasId = null;

    public string $namaObat = '';

    public string $namaFasilitas = '';

    public function mount(int $obat_id): void
    {
        $this->obatId = $obat_id;
        $this->fasilitasId = (int) request()->query('fasilitas_id', 0);

        $obat = Obat::find($obat_id);
        $this->namaObat = $obat?->nama_obat ?? "Obat #{$obat_id}";

        $fasilitas = FasilitasKesehatan::find($this->fasilitasId);
        $this->namaFasilitas = $fasilitas?->nama ?? "Faskes #{$this->fasilitasId}";

        $user = Auth::user();

        if ($user->hasAnyRole(['puskesmas', 'pustu']) && filled($user->fasilitas_kesehatan_id) && $user->fasilitas_kesehatan_id !== $this->fasilitasId) {
            abort(403);
        }
    }

    public function getTitle(): string|HtmlString
    {
        return new HtmlString(
            "Riwayat Stok: <span class=\"font-normal\">{$this->namaObat}</span>"
            ." — <span class=\"font-normal text-gray-500\">{$this->namaFasilitas}</span>"
        );
    }

    protected function getTableQuery(): Builder
    {
        $query = RiwayatStok::query()
            ->where('obat_id', $this->obatId)
            ->where('fasilitas_id', $this->fasilitasId);

        $user = Auth::user();

        if ($user->hasAnyRole(['puskesmas', 'pustu']) && filled($user->fasilitas_kesehatan_id)) {
            $query->where('fasilitas_id', $user->fasilitas_kesehatan_id);
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('tanggal')
                ->label('Tanggal')
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('tipe')
                ->label('Tipe')
                ->badge()
                ->sortable()
                ->formatStateUsing(fn (string $state): string => RiwayatStoksTable::getTipeLabel($state))
                ->color(fn (string $state): string => RiwayatStoksTable::getTipeColor($state)),
            TextColumn::make('jumlah')
                ->label('Jumlah')
                ->sortable()
                ->numeric()
                ->color(fn ($record): string => in_array($record->tipe, ['keluar', 'distribusi_keluar', 'rusak', 'hilang', 'expired']) ? 'danger' : 'success'),
            TextColumn::make('stok_sebelum')
                ->label('Stok Sebelum')
                ->sortable()
                ->numeric()
                ->toggleable(),
            TextColumn::make('stok_sesudah')
                ->label('Stok Sesudah')
                ->sortable()
                ->numeric()
                ->toggleable(),
            TextColumn::make('user.name')
                ->label('User')
                ->sortable()
                ->toggleable(),
            TextColumn::make('keterangan')
                ->label('Keterangan')
                ->toggleable(isToggledHiddenByDefault: true)
                ->limit(50),
            TextColumn::make('referensi_type')
                ->label('Dokumen')
                ->formatStateUsing(fn ($record): string => self::getReferensiLabel($record))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'tanggal';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableRecordAction(): ?string
    {
        return null;
    }

    protected function getTableFilters(): array
    {
        return [
            Filter::make('tanggal')
                ->label('Filter Tanggal')
                ->schema([
                    DatePicker::make('tanggal_dari')
                        ->label('Dari Tanggal'),
                    DatePicker::make('tanggal_sampai')
                        ->label('Sampai Tanggal'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(
                        $data['tanggal_dari'],
                        fn (Builder $q, $date): Builder => $q->whereDate('tanggal', '>=', $date),
                    )
                    ->when(
                        $data['tanggal_sampai'],
                        fn (Builder $q, $date): Builder => $q->whereDate('tanggal', '<=', $date),
                    ),
                ),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    private static function getReferensiLabel($record): string
    {
        if ($record->referensi_type === null) {
            return '-';
        }

        $class = class_basename($record->referensi_type);

        return "{$class} #{$record->referensi_id}";
    }
}
