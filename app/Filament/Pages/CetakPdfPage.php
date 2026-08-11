<?php

namespace App\Filament\Pages;

use App\Services\PdfGenerationService;
use App\Services\PdfSettingsService;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class CetakPdfPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 100;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.cetak-pdf-page';

    public ?string $type = null;

    public ?int $recordId = null;

    public ?array $data = [];

    public ?string $previewUrl = null;

    public bool $settingsCollapsed = false;

    protected $queryString = [
        'type' => ['as' => 'type'],
        'recordId' => ['as' => 'id'],
        'settingsCollapsed' => ['as' => 'sc'],
    ];

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        $type = $parameters['type'] ?? null;
        $id = $parameters['id'] ?? null;

        unset($parameters['type'], $parameters['id']);

        $url = parent::getUrl($parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);

        if ($type && $id) {
            $url .= '?type='.urlencode($type).'&id='.urlencode($id);
        }

        return $url;
    }

    public function mount(): void
    {
        abort_unless($this->type && $this->recordId, 404);
        abort_unless(in_array($this->type, PdfGenerationService::getValidTypes()), 404);

        $settings = PdfSettingsService::getSettings();

        $defaults = [
            'paper_format' => 'A4',
            'orientation' => PdfGenerationService::getDefaultOrientation($this->type),
            'font_family' => $settings['font_family'] ?? 'DejaVu Sans',
            'font_size_kop1' => $settings['font_size_kop1'] ?? '14',
            'font_size_kop2' => $settings['font_size_kop2'] ?? '16',
            'font_size_body' => $settings['font_size_body'] ?? '12',
            'margin_top' => $settings['margin_top'] ?? '18',
            'margin_bottom' => $settings['margin_bottom'] ?? '25',
            'margin_left' => $settings['margin_left'] ?? '18',
            'margin_right' => $settings['margin_right'] ?? '18',
        ];

        $this->form->fill($defaults);

        $this->previewUrl = $this->buildPreviewUrl();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Halaman')
                    ->schema([
                        Select::make('paper_format')
                            ->label('Ukuran Kertas')
                            ->options([
                                'A3' => 'A3',
                                'A4' => 'A4',
                                'A5' => 'A5',
                                'F4' => 'F4 / Folio',
                                'Letter' => 'Letter',
                                'Legal' => 'Legal',
                            ])
                            ->default('A4')
                            ->live(),
                        Radio::make('orientation')
                            ->label('Orientasi')
                            ->options([
                                'portrait' => 'Portrait',
                                'landscape' => 'Landscape',
                            ])
                            ->default('portrait')
                            ->live(),
                    ]),
                Section::make('Huruf')
                    ->schema([
                        Select::make('font_family')
                            ->label('Jenis Huruf')
                            ->options(PdfSettingsService::getFontFamilyOptions())
                            ->default('DejaVu Sans')
                            ->selectablePlaceholder(false)
                            ->live(),
                        Grid::make(1)
                            ->schema([
                                Select::make('font_size_kop1')
                                    ->label('Uk. Kop 1 (pt)')
                                    ->options(self::fontSizeOptions())
                                    ->default('14')
                                    ->live(),
                                Select::make('font_size_kop2')
                                    ->label('Uk. Kop 2 (pt)')
                                    ->options(self::fontSizeOptions())
                                    ->default('16')
                                    ->live(),
                                Select::make('font_size_body')
                                    ->label('Uk. Body & Tanda Tangan (pt)')
                                    ->options(self::fontSizeOptions())
                                    ->default('12')
                                    ->live(),
                            ]),
                    ]),
                Section::make('Margin (mm)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('margin_top')
                                    ->label('Atas')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(50)
                                    ->default(18)
                                    ->live()
                                    ->debounce(500),
                                TextInput::make('margin_bottom')
                                    ->label('Bawah')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(50)
                                    ->default(25)
                                    ->live()
                                    ->debounce(500),
                                TextInput::make('margin_left')
                                    ->label('Kiri')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(50)
                                    ->default(18)
                                    ->live()
                                    ->debounce(500),
                                TextInput::make('margin_right')
                                    ->label('Kanan')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(50)
                                    ->default(18)
                                    ->live()
                                    ->debounce(500),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->refreshPreview();
        }
    }

    public function refreshPreview(): void
    {
        $this->previewUrl = $this->buildPreviewUrl();
    }

    public function download(): void
    {
        $overrides = $this->form->getState();

        $url = route('admin.cetak-preview', [
            'type' => $this->type,
            'id' => $this->recordId,
            'download' => 1,
        ]).'&'.http_build_query($overrides);

        $this->js("window.open('{$url}', '_blank')");
    }

    public function saveDefault(): void
    {
        $data = $this->form->getState();
        $overrides = array_filter($data, fn ($key) => $key !== 'save_as_default', ARRAY_FILTER_USE_KEY);

        PdfSettingsService::setGlobal($overrides);

        Notification::make()
            ->title('Pengaturan berhasil disimpan sebagai default')
            ->success()
            ->send();
    }

    public function toggleSettings(): void
    {
        $this->settingsCollapsed = ! $this->settingsCollapsed;
    }

    public function getTitle(): string
    {
        return match ($this->type) {
            'faktur-distribusi' => 'Cetak Faktur Distribusi',
            'faktur-penerimaan' => 'Cetak Faktur Penerimaan',
            'faktur-permintaan' => 'Cetak Faktur Permintaan',
            'faktur-retur' => 'Cetak Faktur Retur',
            'lplpo' => 'Cetak LPLPO',
            'rko' => 'Cetak RKO',
            'neraca' => 'Cetak Neraca Tahunan',
            default => 'Cetak PDF',
        };
    }

    private function buildPreviewUrl(): string
    {
        $overrides = $this->form->getRawState();
        $overrides['_t'] = time();

        $baseUrl = route('admin.cetak-preview', [
            'type' => $this->type,
            'id' => $this->recordId,
        ]);

        if ($query = http_build_query($overrides)) {
            $baseUrl .= '?'.$query;
        }

        return $baseUrl;
    }

    private static function fontSizeOptions(): array
    {
        $opts = [];
        foreach (range(8, 24) as $px) {
            $opts[(string) $px] = "{$px} pt";
        }

        return $opts;
    }
}
