<?php

namespace App\Filament\Pages;

use App\Models\FasilitasKesehatan;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
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

    public int $tahun = 0;

    public ?int $fasilitasId = null;

    public bool $dryRun = true;

    public array $targets = ['stok_faskes', 'lplpo', 'rko', 'penerimaan', 'pemakaian'];

    public array $previewData = [];

    public array $validationResults = [];

    public array $importResults = [];

    public bool $importCompleted = false;

    public ?array $data = [];

    public function mount(): void
    {
        if ($this->tahun === 0) {
            $this->tahun = (int) now()->year;
        }

        $this->form->fill([
            'tahun' => $this->tahun,
        ]);
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

        $fasilitasId = $state['fasilitas_id'] ?? $this->fasilitasId;
        if (! $fasilitasId && ! Auth::user()?->hasAnyRole(['super_admin', 'admin_dinas'])) {
            $fasilitasId = Auth::user()?->fasilitas_kesehatan_id;
            $this->fasilitasId = $fasilitasId;
        } else {
            $this->fasilitasId = $fasilitasId ? (int) $fasilitasId : null;
        }

        if (! $this->fasilitasId) {
            Notification::make()
                ->title('Faskes wajib dipilih')
                ->body('Pilih fasilitas kesehatan terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $tahun = (int) ($state['tahun'] ?? $this->tahun);
        $this->tahun = $tahun;

        // Single file: FileUpload mengembalikan string (bukan array) saat multiple(false)
        $file = $state['excel_file'] ?? $state['excel_files'] ?? null;
        if (is_array($file)) {
            $file = reset($file) ?: null;
        }

        if (empty($file) || ! Storage::disk('local')->exists($file)) {
            Notification::make()
                ->title('Upload 1 file Excel')
                ->warning()
                ->send();

            return;
        }

        $this->previewData = [];
        $this->validationResults = [];

        $fullPath = Storage::disk('local')->path($file);
        $parsed = $this->getService()->parseFile($fullPath, $this->fasilitasId);
        $validated = $this->getService()->validateData($parsed, $this->fasilitasId, $tahun);

        $faskes = FasilitasKesehatan::find($this->fasilitasId);
        $faskesLabel = $faskes?->nama ?? 'Faskes #'.$this->fasilitasId;

        $this->previewData[$faskesLabel] = array_merge($parsed, $validated, ['file' => $file, 'faskes_label' => $faskesLabel]);
        $this->validationResults[$faskesLabel] = $validated;

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

        $fasilitasId = $state['fasilitas_id'] ?? $this->fasilitasId;
        if (! $fasilitasId && ! Auth::user()?->hasAnyRole(['super_admin', 'admin_dinas'])) {
            $fasilitasId = Auth::user()?->fasilitas_kesehatan_id;
        }
        $this->fasilitasId = $fasilitasId ? (int) $fasilitasId : null;

        if (! $this->fasilitasId) {
            Notification::make()->title('Faskes wajib dipilih')->warning()->send();

            return;
        }

        $tahun = (int) ($state['tahun'] ?? $this->tahun);
        $this->tahun = $tahun;

        $file = $state['excel_file'] ?? $state['excel_files'] ?? null;
        if (is_array($file)) {
            $file = reset($file) ?: null;
        }

        if (empty($file) || ! Storage::disk('local')->exists($file)) {
            return;
        }

        $this->importResults = [];

        $targets = $state['targets'] ?? $this->targets;
        $dryRun = array_key_exists('dry_run', $state) ? (bool) $state['dry_run'] : $this->dryRun;
        $this->targets = $targets;
        $this->dryRun = $dryRun;

        $options = [
            'targets' => $targets,
            'tahun' => $tahun,
            'fasilitas_id' => $this->fasilitasId,
            'dry_run' => $dryRun,
        ];

        $fullPath = Storage::disk('local')->path($file);
        $parsed = $this->getService()->parseFile($fullPath, $this->fasilitasId);

        $validated = $this->getService()->validateData($parsed, $this->fasilitasId, $tahun);
        if (! empty($validated['errors'])) {
            $faskes = FasilitasKesehatan::find($this->fasilitasId);
            $faskesLabel = $faskes?->nama ?? 'Faskes #'.$this->fasilitasId;
            $this->importResults[$faskesLabel] = [
                'faskes_name' => $faskesLabel,
                'dry_run' => $dryRun,
                'targets' => [],
                'errors' => $validated['errors'],
            ];
            $this->importCompleted = true;
            $this->currentStep = 3;
            Notification::make()
                ->title('Import dibatalkan')
                ->body('Validasi gagal. Periksa error di bawah dan sesuaikan Tahun/header file.')
                ->warning()
                ->send();

            return;
        }

        $faskes = FasilitasKesehatan::find($this->fasilitasId);
        $faskesLabel = $faskes?->nama ?? 'Faskes #'.$this->fasilitasId;
        $result = $this->getService()->import($parsed, $options);
        $this->importResults[$faskesLabel] = $result;

        // Auto-train hook: jika pemakaian berhasil diimport (bukan dry-run), queue training untuk faskes ini
        $pemakaianReports = $result['targets']['pemakaian']['reports'] ?? 0;
        if (! $dryRun && in_array('pemakaian', $targets, true) && $pemakaianReports > 0 && empty($result['errors'])) {
            try {
                Artisan::queue('ai:train-models', [
                    '--fasilitas-id' => $this->fasilitasId,
                    '--force' => true,
                ]);

                Notification::make()
                    ->title('Training AI dijadwalkan')
                    ->body("Pemakaian {$pemakaianReports} bulan terimpor — model prediksi untuk {$faskesLabel} akan dilatih di background (queue). Cek menu Prediksi AI → Model Prediksi.")
                    ->info()
                    ->send();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->importCompleted = true;
        $this->currentStep = 3;

        $hasErrors = false;
        foreach ($this->importResults as $resultItem) {
            if (! empty($resultItem['errors'])) {
                $hasErrors = true;
                break;
            }
        }

        Notification::make()
            ->title($dryRun ? 'Dry-run selesai' : 'Import selesai')
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
        $canPickFaskes = Auth::user()?->hasAnyRole(['super_admin', 'admin_dinas']) ?? false;

        return $form
            ->statePath('data')
            ->schema([
                Section::make('Fasilitas & Periode')
                    ->description('1 file = 1 faskes × 1 tahun. Pilih faskes dan tahun sesuai isi template.')
                    ->schema([
                        Select::make('fasilitas_id')
                            ->label('Fasilitas Kesehatan')
                            ->options(fn () => FasilitasKesehatan::where('status', 'aktif')->orderBy('nama')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required($canPickFaskes)
                            ->visible($canPickFaskes)
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->fasilitasId = $state ? (int) $state : null),
                        Select::make('tahun')
                            ->label('Tahun Data')
                            ->options(array_combine(range(2022, 2030), range(2022, 2030)))
                            ->default(fn () => (int) now()->year)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->tahun = (int) $state),
                    ])->columns(2),
                Section::make('Upload File Excel')
                    ->description('Upload 1 file tabulasi (.xlsx) untuk faskes & tahun terpilih.')
                    ->schema([
                        FileUpload::make('excel_file')
                            ->label('File Tabulasi')
                            ->disk('local')
                            ->directory('tabulasi-import')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(10240)
                            ->required()
                            ->helperText(fn ($get) => new HtmlString('1 file = 1 faskes × 1 tahun (disarankan 12 bulan terakhir untuk AI). Template: <a href="'.route('admin.template.prediksi-wide').'?tahun='.($get('tahun') ?? $this->tahun).'" class="text-primary-600 underline">Download Template Prediksi Wide (Opsi A)</a> — header: kode_obat, nama_obat, satuan, harga, YYYY-MM (tahun harus sesuai pilihan, min 6 bulan untuk Gradient Boost, 3 bulan untuk MA), stok_akhir.')),
                    ]),
                Section::make('Konfigurasi Import')
                    ->description('Atur opsi import data')
                    ->schema([
                        Toggle::make('dry_run')
                            ->label('Dry-Run Mode')
                            ->helperText('Jalankan tanpa menyimpan ke database')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->dryRun = $state),
                    ]),
                Section::make('Target Data')
                    ->description('Pilih jenis data yang ingin di-import — centang Pemakaian agar data bisa dipakai Prediksi AI')
                    ->schema([
                        CheckboxList::make('targets')
                            ->options([
                                'stok_faskes' => 'Stok Faskes',
                                'lplpo' => 'LPLPO (12 bulan)',
                                'rko' => 'RKO (tahunan)',
                                'penerimaan' => 'Penerimaan Stok',
                                'pemakaian' => 'Pemakaian Obat (wajib untuk Prediksi AI)',
                            ])
                            ->helperText('Jika Pemakaian tidak dicentang, hasil import TIDAK akan melatih model prediksi (AI hanya baca pemakaian_obat).')
                            ->columns(2)
                            ->default(['stok_faskes', 'lplpo', 'rko', 'penerimaan', 'pemakaian'])
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->targets = $state;

                                if (is_array($state) && ! in_array('pemakaian', $state, true)) {
                                    Notification::make()
                                        ->title('Pemakaian tidak dicentang')
                                        ->body('Prediksi AI tidak akan mendapat data. Centang Pemakaian jika ingin latih model.')
                                        ->warning()
                                        ->send();
                                }
                            }),
                    ]),
            ]);
    }
}
