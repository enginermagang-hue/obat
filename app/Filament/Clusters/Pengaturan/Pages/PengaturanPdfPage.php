<?php

namespace App\Filament\Clusters\Pengaturan\Pages;

use App\Filament\Clusters\Pengaturan\PengaturanCluster;
use App\Services\PdfSettingsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PengaturanPdfPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = PengaturanCluster::class;

    public function getView(): string
    {
        return 'filament.pages.pengaturan-pdf-page';
    }

    public function getTitle(): string
    {
        return 'Pengaturan PDF';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan PDF';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = PdfSettingsService::getSettings();

        if ($path = $settings['logo_path'] ?? null) {
            $publicDisk = Storage::disk('public');
            if (! $publicDisk->exists($path)) {
                $localDisk = Storage::disk('local');
                if ($localDisk->exists($path)) {
                    $publicDisk->put($path, $localDisk->get($path));
                }
            }
        }

        $this->form->fill($settings);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kop Surat')
                    ->description('Pengaturan kop surat default untuk dokumen PDF (Dinas Kesehatan).')
                    ->schema([
                        TextInput::make('kop_baris_1')
                            ->label('Baris 1')
                            ->placeholder('PEMERINTAH KABUPATEN KUPANG'),
                        TextInput::make('kop_baris_2')
                            ->label('Baris 2')
                            ->placeholder('DINAS KESEHATAN KABUPATEN KUPANG'),
                        Textarea::make('kop_alamat')
                            ->label('Alamat')
                            ->rows(3)
                            ->placeholder('Jl. El Tari II, Kec. Kupang Tengah, Kabupaten Kupang, NTT'),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('pdf')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->helperText('Upload logo untuk kop surat. Format PNG/JPG, maks 1MB.'),
                    ]),
                Section::make('Tampilan')
                    ->description('Pengaturan font untuk semua dokumen PDF.')
                    ->schema([
                        Select::make('font_family')
                            ->label('Jenis Huruf')
                            ->options(PdfSettingsService::getFontFamilyOptions())
                            ->default('DejaVu Sans')
                            ->selectablePlaceholder(false),
                        Section::make('Ukuran Font (pt)')
                            ->columns(2)
                            ->schema([
                                Select::make('font_size_kop1')
                                    ->label('Kop Baris 1')
                                    ->options(self::fontSizeOptions())
                                    ->default('14'),
                                Select::make('font_size_kop2')
                                    ->label('Kop Baris 2')
                                    ->options(self::fontSizeOptions())
                                    ->default('16'),
                                Select::make('font_size_body')
                                    ->label('Isi / Body')
                                    ->options(self::fontSizeOptions())
                                    ->default('12'),
                            ]),
                    ]),
                Section::make('Layout Halaman')
                    ->description('Margin halaman dalam milimeter (mm).')
                    ->schema([
                        TextInput::make('margin_top')
                            ->label('Margin Atas (mm)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(50)
                            ->default(18),
                        TextInput::make('margin_bottom')
                            ->label('Margin Bawah (mm)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(50)
                            ->default(25),
                        TextInput::make('margin_left')
                            ->label('Margin Kiri (mm)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(50)
                            ->default(18),
                        TextInput::make('margin_right')
                            ->label('Margin Kanan (mm)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(50)
                            ->default(18),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    private static function fontSizeOptions(): array
    {
        $opts = [];
        foreach (range(8, 24) as $px) {
            $opts[(string) $px] = "{$px} pt";
        }

        return $opts;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (array_key_exists('logo_path', $data)) {
            $logo = $data['logo_path'];
            if (is_array($logo)) {
                $logo = $logo[0] ?? null;
            }
            if (blank($logo)) {
                $existing = PdfSettingsService::getSettings();
                $logo = $existing['logo_path'] ?? null;
            }
            $data['logo_path'] = $logo;
        }

        PdfSettingsService::setGlobal($data);

        Notification::make()
            ->title('Pengaturan PDF berhasil disimpan')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_pengaturan_pdf') ?? false;
    }
}
