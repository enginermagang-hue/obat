<?php

namespace App\Filament\Resources\StokGudangs\Pages;

use App\Filament\Resources\RiwayatStoks\Tables\RiwayatStoksTable;
use App\Filament\Resources\StokGudangs\StokGudangResource;
use App\Models\Obat;
use App\Models\RiwayatStok;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable as HasTableContract;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RiwayatStokObat extends Page implements HasForms, HasTableContract
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = StokGudangResource::class;

    protected string $view = 'filament.pages.stok-gudang.riwayat-stok-obat';

    public ?int $obatId = null;

    public string $namaObat = '';

    public ?string $search = null;

    public ?int $filterBulan = null;

    public int $filterTahun;

    public function mount(int $obat_id): void
    {
        $this->obatId = $obat_id;
        $this->filterTahun = (int) date('Y');

        $obat = Obat::find($obat_id);
        $this->namaObat = $obat?->nama_obat ?? "Obat #{$obat_id}";
    }

    public function getTitle(): string|HtmlString
    {
        return new HtmlString("Riwayat Stok: <span class=\"font-normal\">{$this->namaObat}</span>");
    }

    protected function getForms(): array
    {
        return ['filtersForm'];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Grid::make(4)
                    ->schema([
                        Select::make('filterBulan')
                            ->label('Bulan')
                            ->hiddenLabel()
                            ->prefix('Bulan')
                            ->placeholder('Semua Bulan')
                            ->native(false)
                            ->live()
                            ->options([
                                '1' => 'Januari',
                                '2' => 'Februari',
                                '3' => 'Maret',
                                '4' => 'April',
                                '5' => 'Mei',
                                '6' => 'Juni',
                                '7' => 'Juli',
                                '8' => 'Agustus',
                                '9' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ]),
                        TextInput::make('filterTahun')
                            ->label('Tahun')
                            ->hiddenLabel()
                            ->prefix('Tahun')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->step(1)
                            ->live(),
                    ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = RiwayatStok::query()->where('obat_id', $this->obatId);

        if ($user->hasAnyRole(['super_admin', 'admin_gudang', 'admin_dinas'])) {
            $query->whereNull('fasilitas_id');
        } elseif (filled($user->fasilitas_kesehatan_id)) {
            $query->where('fasilitas_id', $user->fasilitas_kesehatan_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        $query->when($this->search, fn (Builder $q, string $v) => $q->where('keterangan', 'like', "%{$v}%"));
        $query->when($this->filterBulan, fn (Builder $q, int $v) => $q->whereMonth('tanggal', $v));
        $query->when($this->filterTahun, fn (Builder $q, int $v) => $q->whereYear('tanggal', $v));

        return $query;
    }

    public function table(Table $table): Table
    {
        return RiwayatStoksTable::configure($table, [
            'obat.kode_obat',
            'obat.nama_obat',
            'fasilitas.nama',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->dispatch('refreshTable');
    }

    public function updatedFilterBulan(): void
    {
        $this->dispatch('refreshTable');
    }

    public function updatedFilterTahun(): void
    {
        $this->dispatch('refreshTable');
    }

    public function resetFilters(): void
    {
        $this->search = null;
        $this->filterBulan = null;
        $this->filterTahun = (int) date('Y');
        $this->dispatch('refreshTable');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }
}
