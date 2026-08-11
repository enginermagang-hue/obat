<?php

namespace App\Filament\Resources\AlokasiDana;

use App\Models\SumberDana;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class AlokasiDanaPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.alokasi-dana';

    public ?int $tahun = null;

    public ?int $sumber_dana_id = null;

    public ?string $tipe = null;

    protected $queryString = [
        'tahun',
        'sumber_dana_id',
        'tipe',
    ];

    public function mount(): void
    {
        $this->tahun ??= now()->year;
        $this->form->fill([
            'tahun' => $this->tahun,
            'sumber_dana_id' => $this->sumber_dana_id,
            'tipe' => $this->tipe,
        ]);

        $this->dispatch('alokasiDanaFiltersUpdated', [
            'tahun' => $this->tahun,
            'sumber_dana_id' => $this->sumber_dana_id,
            'tipe' => $this->tipe,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => array_combine(
                        range(now()->year - 2, now()->year + 1),
                        range(now()->year - 2, now()->year + 1),
                    ))
                    ->live(),
                Select::make('sumber_dana_id')
                    ->label('Sumber Dana')
                    ->options(fn (): array => SumberDana::query()
                        ->orderBy('tahun', 'desc')
                        ->orderBy('kode')
                        ->get()
                        ->mapWithKeys(fn (SumberDana $sd): array => [
                            $sd->id => "{$sd->kode} ({$sd->tahun})",
                        ])
                        ->toArray())
                    ->placeholder('Semua Sumber Dana')
                    ->searchable()
                    ->nullable()
                    ->live(),
                Select::make('tipe')
                    ->label('Tipe Penggunaan')
                    ->options([
                        'alokasi' => 'Alokasi ke Faskes',
                        'realisasi' => 'Realisasi (Pembelian)',
                        'perencanaan' => 'Perencanaan',
                    ])
                    ->placeholder('Semua Tipe')
                    ->nullable()
                    ->live(),
            ])
            ->columns(3);
    }

    public function updated($property, $value): void
    {
        $this->dispatch('alokasiDanaFiltersUpdated', [
            'tahun' => $this->tahun,
            'sumber_dana_id' => $this->sumber_dana_id,
            'tipe' => $this->tipe,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Alokasi Dana';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Laporan';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Alokasi Penggunaan Dana';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'alokasi-dana';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_alokasi_dana') ?? false;
    }
}
