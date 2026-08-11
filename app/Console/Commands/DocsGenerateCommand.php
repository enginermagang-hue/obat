<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsGenerateCommand extends Command
{
    protected $signature = 'docs:generate
        {--changed= : Comma-separated list of changed file paths}
        {--resource= : Specific resource name to generate docs for}
        {--all : Regenerate all docs}';

    protected $description = 'Generate or update documentation based on code changes';

    private const DOCS_DIR = 'docs';

    private const CODE_DOCS_MAP = [
        'DistribusiObats' => 'Distribusi Obat.md',
        'PermintaanObats' => 'Permintaan Obat.md',
        'ReturObats' => 'Retur Obat.md',
        'InspeksiReturs' => 'Retur Obat.md',
        'PemakaianObats' => 'Pemakaian Obat.md',
        'NeracaTahunans' => 'Neraca Tahunan & LPLPO.md',
        'LaporanLplpos' => 'Neraca Tahunan & LPLPO.md',
        'ModelPrediksis' => 'Prediksi AI.md',
        'PrediksiKebutuhans' => 'Prediksi AI.md',
        'Permissions' => 'Permissions.md',
        'Roles' => 'Permissions.md',
        'OpnameStoks' => 'stok-opname.md',
    ];

    private const NEW_DOCS_MAP = [
        'Obats' => ['file' => 'Obat.md', 'title' => 'Obat'],
        'Suppliers' => ['file' => 'Supplier.md', 'title' => 'Supplier'],
        'Users' => ['file' => 'User & Auth.md', 'title' => 'User & Auth'],
        'FasilitasKesehatans' => ['file' => 'Fasilitas Kesehatan.md', 'title' => 'Fasilitas Kesehatan'],
        'SumberDanas' => ['file' => 'Sumber Dana.md', 'title' => 'Sumber Dana'],
        'ActivityLogs' => ['file' => 'Activity Log.md', 'title' => 'Activity Log'],
        'LaporanRkos' => ['file' => 'Laporan RKO.md', 'title' => 'Laporan RKO'],
        'PenerimaanStoks' => ['file' => 'Penerimaan Stok.md', 'title' => 'Penerimaan Stok'],
    ];

    private const SCHEMA_DOCS_TARGET = 'Skema Database.md';

    public function handle(): int
    {
        $changed = $this->option('changed');
        $resource = $this->option('resource');
        $all = $this->option('all');

        if ($all) {
            $this->generateAllDocs();

            return self::SUCCESS;
        }

        if ($resource !== null) {
            $this->updateDocForResource($resource);

            return self::SUCCESS;
        }

        if ($changed !== null) {
            $files = explode(',', $changed);
            $this->processChangedFiles($files);

            return self::SUCCESS;
        }

        $this->warn('Provide --changed, --resource, or --all flag.');

        return self::FAILURE;
    }

    private function processChangedFiles(array $files): void
    {
        $resources = [];

        foreach ($files as $file) {
            $file = trim($file);

            // Map file path to resource area
            foreach (self::CODE_DOCS_MAP as $area => $doc) {
                if (Str::contains($file, $area)) {
                    $resources[$doc] = $area;
                    break;
                }
            }

            foreach (self::NEW_DOCS_MAP as $area => $config) {
                if (Str::contains($file, $area)) {
                    $resources[$config['file']] = $area;
                    break;
                }
            }

            if (preg_match('#app/(Models|Filament|Services|Policies|Http/Controllers)/#', $file)) {
                $resources[self::SCHEMA_DOCS_TARGET] = $file;
            }

            if (Str::contains($file, 'database/migrations')) {
                $resources[self::SCHEMA_DOCS_TARGET] = $file;
            }
        }

        foreach ($resources as $docFile => $area) {
            $this->updateDocForResource($area);
        }

        $this->info('Docs generation completed for '.count($resources).' target(s).');
    }

    private function updateDocForResource(string $resourceArea): void
    {
        // Determine target file from CODE_DOCS_MAP or NEW_DOCS_MAP
        $docFile = self::CODE_DOCS_MAP[$resourceArea] ?? null;
        $newDocConfig = self::NEW_DOCS_MAP[$resourceArea] ?? null;

        if ($docFile === null && $newDocConfig !== null) {
            $docFile = $newDocConfig['file'];
        }

        if ($docFile === null) {
            // Check if it's a path, not a resource name
            if (Str::contains($resourceArea, ['/', '\\'])) {
                $docFile = self::SCHEMA_DOCS_TARGET;
            } else {
                $this->warn("Unknown resource area: {$resourceArea}");

                return;
            }
        }

        $docPath = self::DOCS_DIR.'/'.$docFile;

        if ($docFile === self::SCHEMA_DOCS_TARGET) {
            $this->updateSchemaDoc($docPath);

            return;
        }

        if (File::exists(base_path($docPath))) {
            $this->updateExistingDoc($docPath, $resourceArea);
        } else {
            $title = $this->resolveDocTitle($resourceArea);
            $this->createNewDoc($docPath, $resourceArea, $title);
        }
    }

    private function resolveDocTitle(string $resourceArea): string
    {
        $newConfig = self::NEW_DOCS_MAP[$resourceArea] ?? null;
        if ($newConfig !== null) {
            return $newConfig['title'];
        }

        return Str::headline($resourceArea);
    }

    private function updateExistingDoc(string $docPath, string $resourceArea): void
    {
        $content = File::get(base_path($docPath));
        $lines = explode("\n", $content);

        $this->info("Updating existing doc: {$docPath}");

        $updated = $content;

        // Update or add Status Implementasi section
        $statusSection = $this->generateStatusSection($resourceArea);
        $updated = $this->updateOrAddSection($updated, 'Status Implementasi', $statusSection);

        // Update or add Daftar File section
        $fileList = $this->generateFileListSection($resourceArea);
        $updated = $this->updateOrAddSection($updated, 'Daftar File', $fileList);

        File::put(base_path($docPath), $updated);
        $this->info("Updated: {$docPath}");
    }

    private function createNewDoc(string $docPath, string $resourceArea, string $title): void
    {
        $this->info("Creating new doc: {$docPath}");

        $modelName = $this->resolveModelFromResource($resourceArea);
        $statusSection = $this->generateStatusSection($resourceArea);
        $modelSection = $this->generateModelSection($modelName);

        $template = "# {$title} — Dokumentasi Fitur\n\n";
        $template .= "## 1. Tujuan\n\n[TODO: Deskripsi tujuan fitur {$title}]\n\n";
        $template .= "## 2. Status Implementasi\n\n{$statusSection}\n";
        $template .= "## 3. Alur / Cara Kerja\n\n[TODO: Jelaskan alur fitur {$title}]\n\n";
        $template .= "## 4. Detail Teknis\n\n";

        if ($modelSection !== '') {
            $template .= "### Model & Relasi\n\n{$modelSection}\n\n";
        }

        $template .= "### Hak Akses\n\n[TODO: Role apa saja yang bisa mengakses]\n\n";
        $template .= "### Aturan Bisnis\n\n[TODO: Business rules khusus]\n\n";

        File::ensureDirectoryExists(base_path(self::DOCS_DIR));
        File::put(base_path($docPath), $template);
        $this->info("Created: {$docPath}");
    }

    private function updateSchemaDoc(string $docPath): void
    {
        $this->info("Updating schema doc: {$docPath}");
    }

    private function generateStatusSection(string $resourceArea): string
    {
        $resourceDir = app_path("Filament/Resources/{$resourceArea}");

        $checks = [
            'Model + Relasi' => $this->checkModelFile($resourceArea),
            'Migration (schema)' => '❌ (periksa manual)',
            'Policy + Gate' => $this->checkPolicyFile($resourceArea),
            'Filament Resource' => File::exists("{$resourceDir}/{$resourceArea}Resource.php"),
            'Form Schema' => $this->checkFileExists($resourceDir, 'Schemas'),
            'Table Config' => $this->checkFileExists($resourceDir, 'Tables'),
        ];

        $table = "| Komponen | Status |\n| :------- | :----: |\n";
        foreach ($checks as $label => $status) {
            $statusIcon = $status ? '✅' : '❌';
            $table .= "| {$label} | {$statusIcon} |\n";
        }

        return $table;
    }

    private function generateModelSection(string $modelName): string
    {
        if ($modelName === '' || ! class_exists("App\\Models\\{$modelName}")) {
            return '';
        }

        $modelClass = "App\\Models\\{$modelName}";
        $reflection = new \ReflectionClass($modelClass);
        $docBlock = $reflection->getDocComment() ?: '';

        // Extract fillable fields
        $fillable = [];
        if ($reflection->hasProperty('fillable')) {
            $prop = $reflection->getProperty('fillable');
            $prop->setAccessible(true);
            $fillable = $prop->getValue(new $modelClass);
        }

        $table = Str::snake(Str::pluralStudly($modelName));
        if ($reflection->hasProperty('table')) {
            $tableProp = $reflection->getProperty('table');
            $tableProp->setAccessible(true);
            $tableValue = $tableProp->getValue(new $modelClass);
            if ($tableValue !== null) {
                $table = $tableValue;
            }
        }

        $output = "**Model:** `{$modelClass}`  \n**Table:** `{$table}`  \n\n**Fillable Fields:**  \n";
        foreach ($fillable as $field) {
            $output .= "- `{$field}`\n";
        }

        return $output;
    }

    private function generateFileListSection(string $resourceArea): string
    {
        $resourceDir = app_path("Filament/Resources/{$resourceArea}");
        $output = "### Files Baru\n\n(Tidak ada)\n\n### Files Dimodifikasi\n\n";

        if (! is_dir($resourceDir)) {
            $output .= "- `app/Filament/Resources/{$resourceArea}/`\n";
        } else {
            $files = File::allFiles($resourceDir);
            foreach ($files as $file) {
                $relativePath = 'app/Filament/Resources/'.$resourceArea.'/'.$file->getRelativePathname();
                $output .= "- `{$relativePath}`\n";
            }
        }

        $modelName = $this->resolveModelFromResource($resourceArea);
        if ($modelName !== '') {
            $modelPath = app_path("Models/{$modelName}.php");
            if (File::exists($modelPath)) {
                $output .= "- `app/Models/{$modelName}.php`\n";
            }

            $policyPath = app_path("Policies/{$modelName}Policy.php");
            if (File::exists($policyPath)) {
                $output .= "- `app/Policies/{$modelName}Policy.php`\n";
            }
        }

        return $output;
    }

    private function updateOrAddSection(string $content, string $sectionName, string $newSectionContent): string
    {
        // Try to find existing section and replace its content
        $pattern = '/## \d+\. '.preg_quote($sectionName, '/').'\s*\n(.*?)(?=\n## |\z)/s';

        if (preg_match($pattern, $content, $matches)) {
            // Calculate the right heading number based on existing pattern
            preg_match('/## (\d+)\. '.preg_quote($sectionName, '/').'/', $matches[0], $numMatches);
            $headingNum = $numMatches[1] ?? '';

            $replacement = "## {$headingNum}. {$sectionName}\n\n{$newSectionContent}";
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Find the last section heading to append
            $lastHeading = strrpos($content, '## ');
            if ($lastHeading !== false) {
                $lastNum = 0;
                preg_match_all('/## (\d+)\./', $content, $numMatches);
                if (! empty($numMatches[1])) {
                    $lastNum = (int) end($numMatches[1]);
                }

                $content .= "\n## ".($lastNum + 1).". {$sectionName}\n\n{$newSectionContent}\n";
            }
        }

        return $content;
    }

    private function resolveModelFromResource(string $resourceArea): string
    {
        $map = [
            'DistribusiObats' => 'DistribusiObat',
            'PermintaanObats' => 'PermintaanObat',
            'ReturObats' => 'ReturObat',
            'InspeksiReturs' => 'InspeksiRetur',
            'PemakaianObats' => 'PemakaianObat',
            'NeracaTahunans' => 'NeracaTahunan',
            'LaporanLplpos' => 'LaporanLplpo',
            'LaporanRkos' => 'LaporanRko',
            'ModelPrediksis' => 'ModelPrediksi',
            'PrediksiKebutuhans' => 'PrediksiKebutuhan',
            'OpnameStoks' => 'OpnameStok',
            'Permissions' => 'Permission',
            'Roles' => 'Role',
            'Obats' => 'Obat',
            'Suppliers' => 'Supplier',
            'Users' => 'User',
            'FasilitasKesehatans' => 'FasilitasKesehatan',
            'SumberDanas' => 'SumberDana',
            'ActivityLogs' => 'ActivityLog',
            'PenerimaanStoks' => 'PenerimaanStok',
            'StokFaskes' => 'StokFaskes',
            'StokGudangs' => 'StokGudang',
            'RiwayatStoks' => 'RiwayatStok',
        ];

        return $map[$resourceArea] ?? Str::singular(class_basename($resourceArea));
    }

    private function checkModelFile(string $resourceArea): bool
    {
        $modelName = $this->resolveModelFromResource($resourceArea);
        if ($modelName === '') {
            return false;
        }

        if ($modelName === 'Permission') {
            return trait_exists('Spatie\Permission\Models\Permission') || class_exists('Spatie\Permission\Models\Permission');
        }

        return File::exists(app_path("Models/{$modelName}.php"));
    }

    private function checkPolicyFile(string $resourceArea): bool
    {
        $modelName = $this->resolveModelFromResource($resourceArea);
        if ($modelName === '') {
            return false;
        }

        if (in_array($modelName, ['Permission', 'Role'], true)) {
            return true; // Spatie package handles these
        }

        return File::exists(app_path("Policies/{$modelName}Policy.php"));
    }

    private function checkFileExists(string $baseDir, string $subDir): bool
    {
        $path = $baseDir.'/'.$subDir;

        return is_dir($path) && count(File::files($path)) > 0;
    }

    private function generateAllDocs(): void
    {
        $this->info('Regenerating all documentation...');

        foreach (self::CODE_DOCS_MAP as $area => $docFile) {
            $this->updateDocForResource($area);
        }

        foreach (self::NEW_DOCS_MAP as $area => $config) {
            $docPath = self::DOCS_DIR.'/'.$config['file'];
            if (! File::exists(base_path($docPath))) {
                $this->updateDocForResource($area);
            }
        }

        $this->info('All docs generation completed.');
    }
}
