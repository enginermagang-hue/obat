<?php

namespace App\Filament\Resources\DashboardAi;

use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class DashboardAiPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.dashboard-ai';

    public ?int $fasilitas_id = null;

    public ?int $obat_id = null;

    public ?int $bulan = null;

    public ?int $tahun = null;

    protected $queryString = [
        'fasilitas_id',
        'obat_id',
        'bulan',
        'tahun',
    ];

    public function mount(): void
    {
        $this->bulan ??= now()->month;
        $this->tahun ??= now()->year;

        $this->form->fill([
            'fasilitas_id' => $this->fasilitas_id,
            'obat_id' => $this->obat_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);

        $this->dispatch('dashboardFiltersUpdated', [
            'fasilitas_id' => $this->fasilitas_id,
            'obat_id' => $this->obat_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('fasilitas_id')
                    ->label('Fasilitas')
                    ->options(FasilitasKesehatan::pluck('nama', 'id'))
                    ->placeholder('Semua Fasilitas')
                    ->nullable()
                    ->searchable()
                    ->live(),
                Select::make('obat_id')
                    ->label('Obat')
                    ->options(Obat::pluck('nama_obat', 'id'))
                    ->placeholder('Semua Obat')
                    ->nullable()
                    ->searchable()
                    ->live(),
                Select::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ])
                    ->live(),
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => array_combine(
                        range(now()->year - 2, now()->year + 1),
                        range(now()->year - 2, now()->year + 1)
                    ))
                    ->live(),
            ])
            ->columns(4);
    }

    public function updated($property, $value): void
    {
        $this->dispatch('dashboardFiltersUpdated', [
            'fasilitas_id' => $this->fasilitas_id,
            'obat_id' => $this->obat_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard AI';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-chart-bar';
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
        return 'Dashboard AI Prediksi';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'dashboard-ai';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin_dinas']) ?? false;
    }
}
