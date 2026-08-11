<?php

namespace Database\Seeders;

use App\Models\AvatarPreset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AvatarPresetSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = public_path('assets/images/avatars');
        $presets = [];

        foreach (['boy', 'girl'] as $kategori) {
            $dir = $basePath.DIRECTORY_SEPARATOR.$kategori;

            if (! is_dir($dir)) {
                continue;
            }

            $files = File::files($dir);

            foreach ($files as $file) {
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $presets[] = [
                    'nama' => strtoupper($filename),
                    'file_path' => 'assets/images/avatars/'.$kategori.'/'.$file->getFilename(),
                    'kategori' => $kategori,
                    'is_active' => true,
                ];
            }
        }

        foreach ($presets as $preset) {
            AvatarPreset::firstOrCreate(
                ['file_path' => $preset['file_path']],
                $preset,
            );
        }
    }
}
