<?php

namespace App\Filament\Clusters\Pengaturan\Pages;

use App\Filament\Clusters\Pengaturan\PengaturanCluster;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RestoreDatabasePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = PengaturanCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?int $navigationSort = 2;

    public function getView(): string
    {
        return 'filament.pages.restore-database-page';
    }

    public function getTitle(): string
    {
        return 'Restore Database';
    }

    public static function getNavigationLabel(): string
    {
        return 'Restore Database';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Restore dari File Backup')
                    ->description('Upload file backup (.zip) yang dihasilkan oleh fitur Backup Database untuk memulihkan data.')
                    ->schema([
                        Placeholder::make('warning')
                            ->label('')
                            ->content(view('filament.components.restore-warning')),
                        FileUpload::make('backup_file')
                            ->label('File Backup (.zip)')
                            ->disk('local')
                            ->directory('restore-temp')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->maxSize(512000)
                            ->required()
                            ->helperText('Upload file .zip dari hasil backup. Maks 500MB.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function restore(): void
    {
        $data = $this->form->getState();
        $filePath = $data['backup_file'] ?? null;

        if (! $filePath) {
            Notification::make()
                ->title('File backup tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($filePath)) {
            Notification::make()
                ->title('File backup tidak ditemukan di storage')
                ->danger()
                ->send();

            return;
        }

        $fullPath = $disk->path($filePath);
        $zip = new ZipArchive;

        if ($zip->open($fullPath) !== true) {
            $this->cleanupTemp($disk, $filePath);
            Notification::make()
                ->title('Gagal membuka file backup')
                ->danger()
                ->send();

            return;
        }

        $sqlContent = null;
        $dbDumpFile = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (str_ends_with(strtolower($name), '.sql')) {
                $dbDumpFile = $name;
                break;
            }
        }

        if ($dbDumpFile) {
            $sqlContent = $zip->getFromName($dbDumpFile);
        }

        $zip->close();

        if (! $sqlContent) {
            $this->cleanupTemp($disk, $filePath);
            Notification::make()
                ->title('File SQL tidak ditemukan di dalam backup')
                ->danger()
                ->send();

            return;
        }

        $tempSqlPath = storage_path('app/restore-temp/restore_'.time().'.sql');
        $tempDir = dirname($tempSqlPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        file_put_contents($tempSqlPath, $sqlContent);

        try {
            $dbConfig = config('database.connections.'.config('database.default'));
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $database = $dbConfig['database'] ?? '';
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            $command = sprintf(
                'mysql -h%s -P%s -u%s %s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $password !== '' ? '-p'.escapeshellarg($password) : '',
                escapeshellarg($database),
                escapeshellarg($tempSqlPath)
            );

            exec($command, $output, $returnCode);

            if (file_exists($tempSqlPath)) {
                unlink($tempSqlPath);
            }

            $this->cleanupTemp($disk, $filePath);

            if ($returnCode !== 0) {
                Notification::make()
                    ->title('Restore gagal')
                    ->body('MySQL error: '.implode("\n", $output))
                    ->danger()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Database berhasil dipulihkan')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            if (file_exists($tempSqlPath)) {
                unlink($tempSqlPath);
            }

            $this->cleanupTemp($disk, $filePath);

            Notification::make()
                ->title('Restore gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function cleanupTemp($disk, string $filePath): void
    {
        $disk->delete($filePath);

        $files = $disk->files('restore-temp');
        foreach ($files as $file) {
            $disk->delete($file);
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_backup') ?? false;
    }
}
