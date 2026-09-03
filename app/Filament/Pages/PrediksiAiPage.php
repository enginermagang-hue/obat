<?php

namespace App\Filament\Pages;

use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class PrediksiAiPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.prediksi-ai';

    public ?int $fasilitas_id = null;

    public ?int $obat_id = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    public string $activeSection = 'prediksi';

    protected $queryString = ['fasilitas_id', 'obat_id', 'bulan', 'tahun'];

    public function mount(): void
    {
        // Default to latest periode with data (next-month predictions), not now() which has no predictions yet
        if ($this->bulan === null || $this->tahun === null) {
            $latest = PrediksiKebutuhan::query()
                ->selectRaw('MAX(CONCAT(LPAD(periode_tahun,4,"0"), "-", LPAD(periode_bulan,2,"0"))) as max_periode')
                ->value('max_periode');
            if ($latest) {
                [$y, $m] = explode('-', $latest);
                $this->tahun = $this->tahun ?? (int) $y;
                $this->bulan = $this->bulan ?? (int) $m;
            } else {
                $this->bulan ??= now()->month;
                $this->tahun ??= now()->year;
            }
        }
        $this->form->fill([
            'fasilitas_id' => $this->fasilitas_id,
            'obat_id' => $this->obat_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);
    }

    public function getHasPrediksiData(): bool
    {
        return PrediksiKebutuhan::exists();
    }

    public function getNearestPeriode(): ?string
    {
        $row = PrediksiKebutuhan::orderBy('periode_tahun')->orderBy('periode_bulan')->first(['periode_bulan', 'periode_tahun']);
        if (! $row) {
            return null;
        }

        return Carbon::create($row->periode_tahun, $row->periode_bulan)->translatedFormat('F Y');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('fasilitas_id')->label('Fasilitas')->options(FasilitasKesehatan::pluck('nama', 'id'))->placeholder('Semua Fasilitas')->nullable()->searchable()->live(),
            Select::make('obat_id')->label('Obat')->options(Obat::pluck('nama_obat', 'id'))->placeholder('Semua Obat')->nullable()->searchable()->live(),
            Select::make('bulan')->label('Bulan')->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])->live(),
            Select::make('tahun')->label('Tahun')->options(fn () => array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))->live(),
        ])->columns(4);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['fasilitas_id', 'obat_id', 'bulan', 'tahun'], true)) {
            $this->resetTable();
        }
    }

    public function getStats(): array
    {
        $modelQuery = ModelPrediksi::query()->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))->when($this->obat_id, fn (Builder $q) => $q->where('obat_id', $this->obat_id));
        $prediksiQuery = PrediksiKebutuhan::query()->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id));

        return [
            'model_aktif' => (clone $modelQuery)->where('status', 'aktif')->count(),
            'obat_diprediksi' => (clone $prediksiQuery)->where('periode_bulan', $this->bulan)->where('periode_tahun', $this->tahun)->distinct('obat_id')->count('obat_id'),
            'rata_akurasi' => (clone $modelQuery)->where('status', 'aktif')->whereNotNull('akurasi_r2')->avg('akurasi_r2'),
            'rata_mae' => (clone $modelQuery)->where('status', 'aktif')->whereNotNull('mae')->avg('mae'),
            'rata_mape' => (clone $modelQuery)->where('status', 'aktif')->whereNotNull('mape')->avg('mape'),
            'faskes_terlatih' => (clone $modelQuery)->where('status', 'aktif')->distinct('fasilitas_id')->count('fasilitas_id'),
        ];
    }

    public function getCriticalAlerts(): Collection
    {
        return PrediksiKebutuhan::query()
            ->select(['prediksi_kebutuhan.*', DB::raw('COALESCE(sf.jumlah,0) as stok_saat_ini'), DB::raw('(prediksi_kebutuhan.jumlah_prediksi - COALESCE(sf.jumlah,0)) as kekurangan')])
            ->leftJoin('stok_faskes as sf', fn ($j) => $j->on('prediksi_kebutuhan.fasilitas_id', '=', 'sf.fasilitas_id')->on('prediksi_kebutuhan.obat_id', '=', 'sf.obat_id'))
            ->where('periode_bulan', $this->bulan)->where('periode_tahun', $this->tahun)
            ->when($this->fasilitas_id, fn (Builder $q) => $q->where('prediksi_kebutuhan.fasilitas_id', $this->fasilitas_id))
            ->when($this->obat_id, fn (Builder $q) => $q->where('prediksi_kebutuhan.obat_id', $this->obat_id))
            ->whereRaw('COALESCE(sf.jumlah,0) < prediksi_kebutuhan.jumlah_prediksi')
            ->orderByDesc('kekurangan')->limit(5)->with(['fasilitas', 'obat'])->get();
    }

    public function getChartData(): array
    {
        $query = PrediksiKebutuhan::query()
            ->selectRaw('periode_tahun, periode_bulan, SUM(jumlah_prediksi) as total')
            ->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))
            ->when($this->obat_id, fn (Builder $q) => $q->where('obat_id', $this->obat_id))
            ->groupBy('periode_tahun', 'periode_bulan')->orderBy('periode_tahun')->orderBy('periode_bulan')->limit(6)->get();

        return [
            'labels' => $query->map(fn ($r) => Carbon::create($r->periode_tahun, $r->periode_bulan)->translatedFormat('M Y'))->toArray(),
            'values' => $query->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PrediksiKebutuhan::query()->with(['fasilitas', 'obat', 'model'])
                    ->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))
                    ->when($this->obat_id, fn (Builder $q) => $q->where('obat_id', $this->obat_id))
                    ->when($this->bulan, fn (Builder $q) => $q->where('periode_bulan', $this->bulan))
                    ->when($this->tahun, fn (Builder $q) => $q->where('periode_tahun', $this->tahun))
            )
            ->emptyStateHeading('Belum ada prediksi untuk filter ini')
            ->emptyStateDescription(fn () => $this->getHasPrediksiData() ? 'Coba ubah filter Bulan/Tahun ke '.$this->getNearestPeriode().' atau jalankan php artisan ai:train-models --force.' : 'Belum ada data prediksi sama sekali. Jalankan php artisan ai:train-models --force.')
            ->emptyStateIcon('heroicon-o-exclamation-triangle')
            ->columns([
                TextColumn::make('fasilitas.nama')->label('Fasilitas')->searchable()->sortable(),
                TextColumn::make('obat.nama_obat')->label('Obat')->searchable()->sortable(),
                TextColumn::make('periode_bulan')->label('Periode')->formatStateUsing(fn ($record) => Carbon::create($record->periode_tahun, $record->periode_bulan)->translatedFormat('F Y'))->sortable(query: fn (Builder $q, string $dir) => $q->orderBy('periode_tahun', $dir)->orderBy('periode_bulan', $dir)),
                TextColumn::make('jumlah_prediksi')->label('Prediksi')->sortable()->weight('bold'),
                TextColumn::make('confidence_lower')->label('CI')->formatStateUsing(fn ($record) => $record->confidence_lower.' – '.$record->confidence_upper)->toggleable(),
                TextColumn::make('metode')->badge()->color(fn (string $state): string => match ($state) {
                    'ann_php' => 'info', 'ai_gradient_boost' => 'success', 'moving_average' => 'warning', default => 'gray'
                })->formatStateUsing(fn (string $state): string => match ($state) {
                    'ann_php' => 'ANN', 'ai_gradient_boost' => 'AI', 'moving_average' => 'MA', default => $state
                }),
                TextColumn::make('model.akurasi_r2')->label('Akurasi')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 1).'%' : '-'),
            ])
            ->defaultSort('periode_tahun', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getModelRecords()
    {
        return ModelPrediksi::query()->with(['fasilitas', 'obat'])
            ->when($this->fasilitas_id, fn (Builder $q) => $q->where('fasilitas_id', $this->fasilitas_id))
            ->when($this->obat_id, fn (Builder $q) => $q->where('obat_id', $this->obat_id))
            ->orderByDesc('updated_at')->limit(10)->get();
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
        return auth()->user()?->hasAnyRole(['super_admin', 'admin_dinas']) ?? false;
    }
}
