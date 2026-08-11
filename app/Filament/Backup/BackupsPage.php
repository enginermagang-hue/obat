<?php

namespace App\Filament\Backup;

use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as BaseBackups;
use ZipArchive;

class BackupsPage extends BaseBackups
{
    protected static ?string $slug = 'backups';

    public function getHeading(): string|Htmlable
    {
        return 'Backup Database';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_backup')
                ->label('Buat Backup')
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->action('openOptionModal'),
        ];
    }

    public function create(string $option = ''): void
    {
        $this->dispatch('close-modal', id: 'backup-option');

        try {
            $filename = date('Y-m-d-H-i-s').'.zip';
            $sql = '';

            if ($option !== 'only-files') {
                $sql = $this->dumpDatabase();
            }

            $zip = new ZipArchive;
            $zipPath = storage_path('app/backups/'.config('backup.backup.name')."/{$filename}");
            $dir = dirname($zipPath);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new Exception('Gagal membuat file zip');
            }

            if ($sql) {
                $zip->addFromString('database.sql', $sql);
            }

            $zip->close();

            Notification::make()
                ->title('Backup berhasil')
                ->body($filename)
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Backup gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function dumpDatabase(): string
    {
        $sql = "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;

            $sql .= "-- Table: {$tableName}\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

            $createSql = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= $createSql[0]->{'Create Table'}.";\n\n";

            $rows = DB::table($tableName)->get();

            if ($rows->isNotEmpty()) {
                $firstRow = (array) $rows->first();
                $columns = implode('`, `', array_keys($firstRow));
                $sql .= "INSERT INTO `{$tableName}` (`{$columns}`) VALUES\n";

                $values = [];
                foreach ($rows as $row) {
                    $row = (array) $row;
                    $rowValues = array_map(fn ($val) => $val === null ? 'NULL' : "'".str_replace("'", "\\'", $val)."'", $row);
                    $values[] = '('.implode(', ', $rowValues).')';
                }

                $sql .= implode(",\n", $values).";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sql;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('manage_backup') ?? false;
    }
}
