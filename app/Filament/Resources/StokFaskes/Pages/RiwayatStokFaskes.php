<?php

namespace App\Filament\Resources\StokFaskes\Pages;

use App\Filament\Resources\RiwayatStoks\Tables\RiwayatStoksTable;
use App\Filament\Resources\StokFaskes\StokFaskesResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\RiwayatStok;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
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

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return RiwayatStoksTable::configure($table, [
            'obat.kode_obat',
            'obat.nama_obat',
            'fasilitas.nama',
        ]);
    }
}
