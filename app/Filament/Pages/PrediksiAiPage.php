<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\PrediksiKebutuhan;
use App\Models\User;
use App\Services\BuatPermintaanService;
use App\Services\PrediksiRekomendasiService;
use App\Services\RkoAccessCheckService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class PrediksiAiPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.prediksi-ai';

    public ?int $fasilitas_id = null;

    public ?string $kategori = null;

    public ?string $cari = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    public int $horizon = 3;

    public int $page = 1;

    public ?int $detailObatId = null;

    public string $activeSection = 'prediksi';

    protected $queryString = ['fasilitas_id', 'kategori', 'cari', 'bulan', 'tahun', 'horizon', 'page'];

    public static function getVisibleFasilitasIds(?User $user = null): array
    {
        $user ??= auth()->user();
        if (! $user || blank($user->fasilitas_kesehatan_id)) {
            return [];
        }
        $ids = [(int) $user->fasilitas_kesehatan_id];
        $faskes = FasilitasKesehatan::find($user->fasilitas_kesehatan_id);
        if ($faskes?->tipe === 'puskesmas') {
            $ids = array_merge($ids, $faskes->pustu()->pluck('id')->map(fn ($v) => (int) $v)->toArray());
        }

        return array_values(array_unique($ids));
    }

    public function mount(): void
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        if ($isFaskes && blank($this->fasilitas_id)) {
            $this->fasilitas_id = (int) $user->fasilitas_kesehatan_id;
        }
        if ($isFaskes && blank($this->tahun)) {
            $periode = app(RkoAccessCheckService::class)->getPeriodeTahun($user);
            if (filled($periode)) {
                $this->tahun = (int) $periode;
            }
        }

        if ($this->bulan === null || $this->tahun === null) {
            $latestQuery = PrediksiKebutuhan::query();
            if ($isFaskes) {
                $visibleIds = self::getVisibleFasilitasIds($user);
                if (! empty($visibleIds)) {
                    $latestQuery->whereIn('fasilitas_id', $visibleIds);
                }
            }
            $earliest = $latestQuery
                ->selectRaw('MIN(CONCAT(LPAD(periode_tahun,4,"0"), "-", LPAD(periode_bulan,2,"0"))) as min_periode')
                ->value('min_periode');
            if ($earliest) {
                [$y, $m] = explode('-', $earliest);
                $this->tahun = $this->tahun ?? (int) $y;
                $this->bulan = $this->bulan ?? (int) $m;
            } else {
                $this->bulan ??= now()->month;
                $this->tahun ??= now()->year;
            }
        }

        $this->form->fill([
            'fasilitas_id' => $this->fasilitas_id,
            'kategori' => $this->kategori,
            'cari' => $this->cari,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);

        $this->dispatchPrediksiFilters();
    }

    public function getHasPrediksiData(): bool
    {
        return PrediksiKebutuhan::exists();
    }

    public function getCanBuatPo(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            return false;
        }

        return filled($user->fasilitas_kesehatan_id);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $visibleIds = $isFaskes ? self::getVisibleFasilitasIds($user) : [];

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
            Select::make('kategori')
                ->label('Kategori')
                ->options(fn (): array => Obat::whereNotNull('kategori')->distinct()->pluck('kategori', 'kategori')->toArray())
                ->placeholder('Semua')
                ->nullable()
                ->searchable()
                ->live(),
            TextInput::make('cari')
                ->label('Cari Obat')
                ->placeholder('Cari nama obat...')
                ->live(debounce: 400),
            Select::make('bulan')
                ->label('Bulan')
                ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                ->live(),
            Select::make('tahun')
                ->label('Tahun')
                ->options(fn () => array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))
                ->live(),
        ])->columns(5);
    }

    public function setHorizon(int $horizon): void
    {
        $this->horizon = $horizon;
        $this->page = 1;
        $this->dispatchPrediksiFilters();
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function updated(): void
    {
        $this->page = 1;
        $this->dispatchPrediksiFilters();
    }

    public function dispatchPrediksiFilters(): void
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        $this->dispatch('prediksiFiltersUpdated', [
            'fasilitas_id' => $this->fasilitas_id,
            'visible_fasilitas_ids' => $isFaskes && blank($this->fasilitas_id) ? self::getVisibleFasilitasIds($user) : null,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'horizon' => $this->horizon,
        ]);
    }

    public function service(): PrediksiRekomendasiService
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);

        return new PrediksiRekomendasiService(
            fasilitasId: $this->fasilitas_id,
            bulan: (int) ($this->bulan ?? now()->month),
            tahun: (int) ($this->tahun ?? now()->year),
            horizon: $this->horizon,
            kategori: $this->kategori,
            cari: $this->cari,
            visibleFasilitasIds: $isFaskes && blank($this->fasilitas_id) ? self::getVisibleFasilitasIds($user) : null,
        );
    }

    public function getRekomendasiRows(): array
    {
        return $this->service()->rows();
    }

    public function getKpi(): array
    {
        $modelAkurasi = $this->getRataAkurasiModel();

        return array_merge($this->service()->kpi($modelAkurasi), [
            'akurasi_model' => $modelAkurasi,
        ]);
    }

    public function getInsightAi(): array
    {
        $defisit = collect($this->getRekomendasiRows())->where('rekom', '>', 0)->values();
        $top = $defisit->first();
        $second = $defisit->get(1);
        $akurasi = $this->getRataAkurasiModel();

        return [
            'defisit_count' => $defisit->count(),
            'primary' => $top['nama_obat'] ?? null,
            'primary_rekom' => $top['rekom'] ?? 0,
            'primary_satuan' => $top['satuan'] ?? null,
            'secondary' => $second['nama_obat'] ?? null,
            'secondary_rekom' => $second['rekom'] ?? 0,
            'fasilitas' => $this->getFasilitasNama(),
            'horizon' => $this->horizon,
            'confidence' => round($akurasi * 100, 1),
        ];
    }

    public function getLonjakan(): array
    {
        return $this->service()->lonjakan(5);
    }

    public function getFasilitasNama(): string
    {
        if ($this->fasilitas_id) {
            return FasilitasKesehatan::find($this->fasilitas_id)?->nama ?? 'Fasilitas Terpilih';
        }

        return 'Semua Puskesmas';
    }

    public function getRataAkurasiModel(): float
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $visibleIds = $isFaskes ? self::getVisibleFasilitasIds($user) : [];

        return (float) ModelPrediksi::query()
            ->where('status', 'aktif')
            ->whereNotNull('akurasi_r2')
            ->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))
            ->when($isFaskes && blank($this->fasilitas_id) && ! empty($visibleIds), fn (Builder $q) => $q->whereIn('fasilitas_id', $visibleIds))
            ->avg('akurasi_r2') ?? 0.0;
    }

    public function getModelRecords()
    {
        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $visibleIds = $isFaskes ? self::getVisibleFasilitasIds($user) : [];

        return ModelPrediksi::query()->with(['fasilitas', 'obat'])
            ->when($isFaskes && blank($this->fasilitas_id) && ! empty($visibleIds), fn (Builder $q) => $q->whereIn('fasilitas_id', $visibleIds))
            ->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))
            ->orderByDesc('updated_at')->limit(10)->get();
    }

    public function exportUrl(): string
    {
        return route('admin.prediksi.cetak-xls', array_filter([
            'fasilitas_id' => $this->fasilitas_id,
            'kategori' => $this->kategori,
            'cari' => $this->cari,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'horizon' => $this->horizon,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function buatPo(): void
    {
        $rows = collect($this->getRekomendasiRows())->where('rekom', '>', 0)->values();
        $this->createPermintaan($rows);
    }

    public function buatPoObat(int $obatId): void
    {
        $row = collect($this->getRekomendasiRows())->firstWhere('obat_id', $obatId);
        $this->createPermintaan(collect([$row])->filter()->values());
        $this->closeDetail();
    }

    public function showDetail(int $obatId): void
    {
        $this->detailObatId = $obatId;
        $this->dispatch('open-modal', id: 'prediksi-detail');
    }

    public function closeDetail(): void
    {
        $this->detailObatId = null;
        $this->dispatch('close-modal', id: 'prediksi-detail');
    }

    public function getDetailData(): ?array
    {
        if (! $this->detailObatId) {
            return null;
        }

        return $this->service()->detail($this->detailObatId);
    }

    public function mintaDistribusi(int $obatId): void
    {
        $row = collect($this->getRekomendasiRows())->firstWhere('obat_id', $obatId);
        $permintaan = $this->createPermintaan(collect([$row])->filter()->values());
        $this->closeDetail();

        if ($permintaan) {
            $this->redirect(PermintaanObatResource::getUrl('edit', ['record' => $permintaan->id]));
        }
    }

    protected function createPermintaan(Collection $rows): ?PermintaanObat
    {
        if (! $this->getCanBuatPo()) {
            Notification::make()->title('Hanya petugas faskes yang dapat membuat permintaan')->warning()->send();

            return null;
        }

        $user = auth()->user();
        $isFaskes = $user && filled($user->fasilitas_kesehatan_id);
        $faskesId = $this->fasilitas_id ?? ($isFaskes ? (int) $user->fasilitas_kesehatan_id : null);

        if (! $faskesId) {
            Notification::make()->title('Pilih Puskesmas terlebih dahulu')->warning()->send();

            return null;
        }

        return BuatPermintaanService::buat(
            $faskesId,
            $rows,
            'Dibuat otomatis dari Prediksi AI ('.Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y').', '.$this->horizon.' bulan)',
        );
    }

    public function trainModel(int $modelId): void
    {
        $record = ModelPrediksi::findOrFail($modelId);
        $exit = Artisan::call('ai:train-models', ['--fasilitas-id' => $record->fasilitas_id, '--obat-id' => $record->obat_id, '--force' => true]);
        $exit === 0
            ? Notification::make()->title('Training berhasil (ANN per faskes+obat)')->success()->send()
            : Notification::make()->title('Training gagal')->body(Artisan::output())->danger()->send();
    }

    public function trainAll(): void
    {
        $exit = Artisan::call('ai:train-models', ['--force' => true]);
        $exit === 0
            ? Notification::make()->title('Training semua faskes+obat berhasil')->success()->send()
            : Notification::make()->title('Training gagal')->body(Artisan::output())->danger()->send();
    }

    public static function getNavigationLabel(): string
    {
        return 'Prediksi AI';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $user = auth()->user();
        if ($user && filled($user->fasilitas_kesehatan_id) && $user->hasPermissionTo('view_prediksi_kebutuhan')) {
            return 'Laporan';
        }

        return 'Ai Service';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Prediksi AI';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasPermissionTo('view_prediksi_kebutuhan') ?? false;
    }
}
