<?php

namespace App\Console\Commands;

use App\Services\TabulasiImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DebugTabulasiParseCommand extends Command
{
    protected $signature = 'tabulasi:debug-parse {file : Path ke file xlsx (relative storage/app atau absolute)} {--tahun= : Tahun untuk validasi} {--faskes= : ID faskes override}';

    protected $description = 'Debug parse file tabulasi: dump header, periode_columns, sample periode_map';

    public function handle(): int
    {
        $fileArg = $this->argument('file');
        $tahun = $this->option('tahun') ? (int) $this->option('tahun') : null;
        $faskesId = $this->option('faskes') ? (int) $this->option('faskes') : null;

        $path = $fileArg;
        if (! file_exists($path)) {
            // coba di storage/app/private/tabulasi-import
            $alt = Storage::disk('local')->path($fileArg);
            if (file_exists($alt)) {
                $path = $alt;
            } else {
                // coba storage/app
                $alt2 = storage_path('app/'.$fileArg);
                if (file_exists($alt2)) {
                    $path = $alt2;
                }
            }
        }

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$fileArg} (resolved: {$path})");
            $this->line('Coba: php artisan tabulasi:debug-parse storage/app/private/tabulasi-import/<file>.xlsx --tahun=2024');

            return 1;
        }

        $this->info("Parsing: {$path}");
        $service = new TabulasiImportService($faskesId);
        $data = $service->parseFile($path, $faskesId);

        $this->line('Format: '.($data['format'] ?? '-'));
        $this->line('Faskes: '.($data['faskes_name'] ?? '-'));
        $this->line('Total rows: '.($data['total_rows'] ?? 0));
        $this->line('Periode columns: '.implode(', ', $data['periode_columns'] ?? []));
        $this->line('Tahun dominan: '.($data['tahun_dominan'] ?? '-'));

        if (! empty($data['obat'])) {
            $sample = $data['obat'][0];
            $this->line('Sample obat[0]: '.json_encode([
                'kode_obat' => $sample['kode_obat'],
                'periode_map' => $sample['periode_map'] ?? null,
                'total_pemakaian' => $sample['total_pemakaian'] ?? 0,
                'stok_akhir_des' => $sample['stok_akhir_des'] ?? 0,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // dump raw headers row 1 via PhpSpreadsheet
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $maxCol = $sheet->getHighestColumn();
            $maxColIndex = Coordinate::columnIndexFromString($maxCol);
            $headers = [];
            for ($c = 1; $c <= $maxColIndex; $c++) {
                $headers[] = $sheet->getCell(Coordinate::stringFromColumnIndex($c).'1')->getValue();
            }
            $this->line('Raw headers row1: '.json_encode($headers, JSON_UNESCAPED_UNICODE));
            // check numeric raw values row 2
            $this->line('Row2 raw periode values:');
            $periodeCols = $data['periode_columns'] ?? [];
            foreach (array_slice($periodeCols, 0, 5) as $p) {
                $this->line("  $p => ".json_encode($sample['periode_map'][$p] ?? null));
            }
        }

        if ($tahun !== null) {
            $this->line("--- Validate tahun={$tahun} faskes=".($faskesId ?? 'null').' ---');
            $validated = $service->validateData($data, $faskesId, $tahun);
            $this->line('Errors: '.json_encode($validated['errors'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('Warnings: '.json_encode($validated['warnings'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        // simulate import dryRun
        if ($tahun !== null && $faskesId !== null) {
            $res = $service->import($data, ['targets' => ['pemakaian', 'stok_faskes'], 'tahun' => $tahun, 'fasilitas_id' => $faskesId, 'dry_run' => true]);
            $this->line('Import dryRun result: '.json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return 0;
    }
}
