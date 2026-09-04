<?php

namespace App\Filament\Pages;

use App\Models\FasilitasKesehatan;
use App\Models\User;
use App\Services\AnalisisObatService;
use App\Services\BuatPermintaanService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class AnalisisObatPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.analisis-obat';

    public ?int $fasilitas_id = null;

    public ?int $tahun = null;

    protected $queryString = ['fasilitas_id', 'tahun'];

    public static function getVisibleFasilitasIds(?User $user = null): array
    {
        return PrediksiAiPage::getVisibleFasilitasIds($user);
    }

    public function mount(): void
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        if ($isFaskes && blank($this->fasilitas_id)) {
            $this->fasilitas_id = (int) $user->fasilitas_kesehatan_id;
        }

        if ($this->tahun === null) {
            $this->tahun = $this->defaultTahun();
        }

        $this->form->fill([
            'fasilitas_id' => $this->fasilitas_id,
            'tahun' => $this->tahun,
        ]);

        $this->dispatchAnalisisFilters();
    }

    protected function defaultTahun(): int
    {
        $driver = DB::connection()->getDriverName();
        $ymExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', tanggal_pemakaian)"
            : "DATE_FORMAT(tanggal_pemakaian, '%Y-%m')";

        $rows = DB::table('pemakaian_obat')
            ->selectRaw("{$ymExpr} as ym")
            ->distinct()
            ->pluck('ym');

        $byYear = [];
        foreach ($rows as $ym) {
            [$y, $m] = explode('-', (string) $ym);
            $byYear[(int) $y][(int) $m] = true;
        }

        if (empty($byYear)) {
            return now()->year;
        }

        krsort($byYear);
        foreach ($byYear as $y => $months) {
            if (count($months) >= 12) {
                return $y;
            }
        }

        return (int) array_key_first($byYear);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $visibleIds = $isFaskes ? self::getVisibleFasilitasIds($user) : [];

        $min = DB::table('pemakaian_obat')->min('tanggal_pemakaian');
        $max = DB::table('pemakaian_obat')->max('tanggal_pemakaian');
        $years = ($min && $max)
            ? range((int) substr($max, 0, 4), (int) substr($min, 0, 4))
            : [now()->year];

        return $schema->components([
            Select::make('fasilitas_id')
                ->label('Puskesmas')
                ->options(function () use ($isFaskes, $visibleIds): array {
                    if ($isFaskes && ! empty($visibleIds)) {
                        return FasilitasKesehatan::whereIn('id', $visibleIds)->pluck('nama', 'id')->toArray();
                    }

                    return FasilitasKesehatan::pluck('nama', 'id')->toArray();
                })
                ->placeholder('Semua Puskesmas')
                ->nullable()
                ->searchable()
                ->disabled($isFaskes && count($visibleIds) === 1)
                ->live(),
            Select::make('tahun')
                ->label('Tahun Analisis')
                ->options(array_combine($years, $years))
                ->live(),
        ])->columns(2);
    }

    public function updated(): void
    {
        $this->dispatchAnalisisFilters();
    }

    public function dispatchAnalisisFilters(): void
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        $this->dispatch('analisisFiltersUpdated', [
            'fasilitas_id' => $this->fasilitas_id,
            'visible_fasilitas_ids' => $isFaskes && blank($this->fasilitas_id) ? self::getVisibleFasilitasIds($user) : null,
            'tahun' => $this->tahun,
        ]);
    }

    public function service(): AnalisisObatService
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        return new AnalisisObatService(
            fasilitasId: $this->fasilitas_id,
            tahun: (int) ($this->tahun ?? now()->year),
            visibleFasilitasIds: $isFaskes && blank($this->fasilitas_id) ? self::getVisibleFasilitasIds($user) : null,
        );
    }

    public function getKpi(): array
    {
        return $this->service()->kpi();
    }

    public function getRingkasan(): array
    {
        return $this->service()->ringkasan();
    }

    public function getAbven(): array
    {
        return $this->service()->abven();
    }

    public function getRisiko(): array
    {
        return $this->service()->risiko(10);
    }

    public function getTrenMusim(): array
    {
        return $this->service()->tren(3)['musim'] ?? [];
    }

    public function getRekomendasi(): array
    {
        return $this->service()->rekomendasi();
    }

    public function getScopeNama(): string
    {
        if ($this->fasilitas_id) {
            return FasilitasKesehatan::find($this->fasilitas_id)?->nama ?? 'Fasilitas Terpilih';
        }

        return 'Semua Puskesmas';
    }

    public function buatPoObat(int $obatId): void
    {
        $row = collect($this->service()->rekomendasiRows())->firstWhere('obat_id', $obatId);
        $this->createPermintaan(collect([$row])->filter()->where('rekom', '>', 0)->values());
    }

    public function buatPoRekomendasi(int $index): void
    {
        $card = $this->getRekomendasi()[$index] ?? null;
        if (! $card || empty($card['obat_ids'])) {
            Notification::make()->title('Tidak ada item pada rekomendasi ini')->info()->send();

            return;
        }

        $rows = collect($this->service()->rekomendasiRows())
            ->whereIn('obat_id', $card['obat_ids'])
            ->where('rekom', '>', 0)
            ->values();

        $this->createPermintaan($rows);
    }

    protected function createPermintaan(Collection $rows): void
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $faskesId = $this->fasilitas_id ?? ($isFaskes ? (int) $user->fasilitas_kesehatan_id : null);

        if (! $faskesId) {
            Notification::make()->title('Pilih Puskesmas terlebih dahulu')->warning()->send();

            return;
        }

        BuatPermintaanService::buat(
            $faskesId,
            $rows,
            'Dibuat otomatis dari Analisis Obat (Tahun '.$this->tahun.')',
            'Analisis Obat',
        );
    }

    public static function getNavigationLabel(): string
    {
        return 'Analisis Obat';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Ai Service';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Analisis Obat';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasPermissionTo('view_prediksi_kebutuhan') ?? false;
    }
}
