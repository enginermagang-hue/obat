<?php

namespace App\Filament\Pages;

use App\Services\TabulasiImportService;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportTabulasiPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Import Tabulasi';

    protected static ?int $navigationSort = 85;

    protected string $view = 'filament.pages.import-tabulasi-page';

    public function getTitle(): string
    {
        return 'Import Data Tabulasi';
    }

    public int $currentStep = 1;

    public int $tahun = 2024;

    public bool $autoCreateFaskes = true;

    public bool $dryRun = true;

    public array $targets = ['stok_faskes', 'lplpo', 'rko', 'penerimaan', 'pemakaian'];

    public array $previewData = [];

    public array $validationResults = [];

    public array $importResults = [];

    public bool $importCompleted = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getService(): TabulasiImportService
    {
        if (! isset($this->service)) {
            $this->service = new TabulasiImportService(Auth::id());
        }

        return $this->service;
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = $step;
    }

    public function runPreview(): void
    {
        $this->form->validate();
        $state = $this->form->getState();
        $files = $state['excel_files'] ?? [];

        if (empty($files)) {
            Notification::make()
                ->title('Upload minimal 1 file Excel')
                ->warning()
                ->send();

            return;
        }

        $this->previewData = [];
        $this->validationResults = [];

        foreach ($files as $file) {
            if (! Storage::disk('local')->exists($file)) {
                continue;
            }

            $fullPath = Storage::disk('local')->path($file);
            $parsed = $this->getService()->parseFile($fullPath);
            $validated = $this->getService()->validateData($parsed);
            $faskesName = $parsed['faskes_name'] ?? basename($file, '.xlsx');

            $this->previewData[$faskesName] = array_merge($parsed, $validated, ['file' => $file]);
            $this->validationResults[$faskesName] = $validated;
        }

        if (empty($this->previewData)) {
            Notification::make()
                ->title('Gagal membaca file')
                ->body('Pastikan file yang di-upload adalah file tabulasi yang valid.')
                ->danger()
                ->send();

            return;
        }

        $this->currentStep = 2;
    }

    public function runImport(): void
    {
        $state = $this->form->getState();
        $files = $state['excel_files'] ?? [];

        if (empty($files)) {
            return;
        }

        $this->importResults = [];

        $options = [
            'targets' => $this->targets,
            'tahun' => $this->tahun,
            'auto_create_faskes' => $this->autoCreateFaskes,
            'dry_run' => $this->dryRun,
        ];

        foreach ($files as $file) {
            if (! Storage::disk('local')->exists($file)) {
                continue;
            }

            $fullPath = Storage::disk('local')->path($file);
            $parsed = $this->getService()->parseFile($fullPath);
            $faskesName = $parsed['faskes_name'] ?? basename($file, '.xlsx');
            $result = $this->getService()->import($parsed, $options);
            $this->importResults[$faskesName] = $result;
        }

        $this->importCompleted = true;
        $this->currentStep = 3;

        $hasErrors = false;
        foreach ($this->importResults as $result) {
            if (! empty($result['errors'])) {
                $hasErrors = true;
                break;
            }
        }

        Notification::make()
            ->title($this->dryRun ? 'Dry-run selesai' : 'Import selesai')
            ->body($hasErrors ? 'Terdapat error. Lihat detail di bawah.' : 'Semua data berhasil diproses.')
            ->status($hasErrors ? 'warning' : 'success')
            ->send();
    }

    public function resetWizard(): void
    {
        $this->currentStep = 1;
        $this->previewData = [];
        $this->validationResults = [];
        $this->importResults = [];
        $this->importCompleted = false;
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Upload File Excel')
                    ->description('Upload file tabulasi (.xlsx) yang ingin di-import. Mendukung multiple file.')
                    ->schema([
                        FileUpload::make('excel_files')
                            ->label('File Tabulasi')
                            ->disk('local')
                            ->directory('tabulasi-import')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(10)
                            ->maxSize(10240)
                            ->required()
                            ->helperText('Format: file Excel tabulasi obat (maks 10MB per file). Anda bisa upload sekaligus beberapa file.'),
                    ]),
                Section::make('Konfigurasi Import')
                    ->description('Atur opsi import data')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tahun')
                                ->label('Tahun Data')
                                ->options(array_combine(range(2022, 2030), range(2022, 2030)))
                                ->default(2024)
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state) => $this->tahun = (int) $state),
                            Toggle::make('dry_run')
                                ->label('Dry-Run Mode')
                                ->helperText('Jalankan tanpa menyimpan ke database')
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(fn ($state) => $this->dryRun = $state),
                            Toggle::make('auto_create_faskes')
                                ->label('Buat Faskes Otomatis')
                                ->helperText('Buat faskes baru jika belum ada di database')
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(fn ($state) => $this->autoCreateFaskes = $state),
                        ]),
                    ]),
                Section::make('Target Data')
                    ->description('Pilih jenis data yang ingin di-import')
                    ->schema([
                        CheckboxList::make('targets')
                            ->options([
                                'stok_faskes' => 'Stok Faskes',
                                'lplpo' => 'LPLPO (12 bulan)',
                                'rko' => 'RKO (tahunan)',
                                'penerimaan' => 'Penerimaan Stok',
                                'pemakaian' => 'Pemakaian Obat',
                            ])
                            ->columns(2)
                            ->default(['stok_faskes', 'lplpo', 'rko', 'penerimaan', 'pemakaian'])
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->targets = $state),
                    ]),
            ]);
    }
}
