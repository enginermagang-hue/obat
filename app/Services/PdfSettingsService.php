<?php

namespace App\Services;

use App\Models\PengaturanLaporan;
use Illuminate\Support\Facades\Storage;

class PdfSettingsService
{
    const GRUP = 'pdf';

    const GOOGLE_FONTS = [
        'Noto Sans' => 'Noto+Sans:wght@400;700',
        'Noto Serif' => 'Noto_Serif:wght@400;700',
        'Roboto' => 'Roboto:wght@400;700',
        'Open Sans' => 'Open+Sans:wght@400;700',
        'Lato' => 'Lato:wght@400;700',
        'Merriweather' => 'Merriweather:wght@400;700',
        'Source Sans 3' => 'Source+Sans+3:wght@400;700',
        'IBM Plex Sans' => 'IBM+Plex+Sans:wght@400;700',
        'IBM Plex Serif' => 'IBM+Plex+Serif:wght@400;700',
        'Libre Baskerville' => 'Libre+Baskerville:wght@400;700',
    ];

    const SYSTEM_FONTS = [
        'DejaVu Sans' => 'DejaVu Sans',
        'DejaVu Serif' => 'DejaVu Serif',
        'DejaVu Sans Mono' => 'DejaVu Sans Mono',
    ];

    const DEFAULTS = [
        'kop_baris_1' => 'PEMERINTAH KABUPATEN KUPANG',
        'kop_baris_2' => 'DINAS KESEHATAN KABUPATEN KUPANG',
        'kop_alamat' => 'Jl. El Tari II, Kec. Kupang Tengah, Kabupaten Kupang, NTT',
        'logo_path' => null,
        'font_family' => 'DejaVu Sans',
        'font_size' => '12',
        'font_size_kop1' => '14',
        'font_size_kop2' => '16',
        'font_size_body' => '12',
        'margin_top' => '18',
        'margin_bottom' => '25',
        'margin_left' => '18',
        'margin_right' => '18',
        'paper_format' => 'A4',
        'orientation' => 'portrait',
    ];

    /**
     * Ambil semua setting PDF untuk suatu faskes (merge global + faskes).
     */
    public static function getSettings(?int $fasilitasId = null): array
    {
        $saved = PengaturanLaporan::getAllForFaskes(self::GRUP, $fasilitasId);

        return array_merge(self::DEFAULTS, $saved);
    }

    /**
     * Ambil setting kop surat (baris_1, baris_2, alamat, logo_path).
     */
    public static function getKopSurat(?int $fasilitasId = null): array
    {
        $settings = self::getSettings($fasilitasId);

        $logoPath = $settings['logo_path'];
        $logoSrc = null;

        // Konversi ke base64 data URI agar dompdf bisa render.
        // Path filesystem sering gagal di dompdf (apalagi Windows campuran slash).
        if ($logoPath) {
            if (str_contains($logoPath, '://') || str_starts_with($logoPath, 'data:')) {
                // Sudah URL atau data URI — pakai langsung
                $logoSrc = $logoPath;
            } elseif (str_starts_with($logoPath, '/')) {
                // Absolute filesystem path — baca & konversi
                if (file_exists($logoPath)) {
                    $mime = mime_content_type($logoPath) ?: 'image/png';
                    $data = file_get_contents($logoPath);
                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($data);
                }
            } else {
                // Relative storage path — cari di public & local disk
                foreach (['public', 'local'] as $disk) {
                    $fullPath = Storage::disk($disk)->path($logoPath);
                    if (file_exists($fullPath)) {
                        $mime = mime_content_type($fullPath) ?: 'image/png';
                        $data = file_get_contents($fullPath);
                        $logoSrc = 'data:'.$mime.';base64,'.base64_encode($data);
                        break;
                    }
                }
            }
        }

        return [
            'baris_1' => $settings['kop_baris_1'],
            'baris_2' => $settings['kop_baris_2'],
            'alamat' => $settings['kop_alamat'],
            'logo_path' => $logoSrc,
        ];
    }

    /**
     * Ambil setting layout (font, margins).
     */
    public static function getLayout(?int $fasilitasId = null): array
    {
        $settings = self::getSettings($fasilitasId);

        return [
            'font_family' => $settings['font_family'],
            'font_src' => self::isGoogleFont($settings['font_family'])
                ? 'google_fonts'
                : 'system',
            'font_size' => $settings['font_size'],
            'font_size_kop1' => $settings['font_size_kop1'],
            'font_size_kop2' => $settings['font_size_kop2'],
            'font_size_body' => $settings['font_size_body'],
            'margin_top' => $settings['margin_top'],
            'margin_bottom' => $settings['margin_bottom'],
            'margin_left' => $settings['margin_left'],
            'margin_right' => $settings['margin_right'],
            'paper_format' => $settings['paper_format'] ?? 'A4',
            'orientation' => $settings['orientation'] ?? 'portrait',
        ];
    }

    public static function getLayoutWithOverrides(array $overrides = [], ?int $fasilitasId = null): array
    {
        $layout = self::getLayout($fasilitasId);

        $allowed = [
            'font_family', 'font_size', 'font_size_kop1',
            'font_size_kop2', 'font_size_body',
            'margin_top', 'margin_bottom', 'margin_left', 'margin_right',
            'paper_format', 'orientation',
        ];

        foreach ($allowed as $key) {
            if (isset($overrides[$key]) && $overrides[$key] !== '') {
                $layout[$key] = (string) $overrides[$key];
            }
        }

        $layout['font_src'] = self::isGoogleFont($layout['font_family'])
            ? 'google_fonts'
            : 'system';

        return $layout;
    }

    public static function isGoogleFont(string $fontFamily): bool
    {
        return array_key_exists($fontFamily, self::GOOGLE_FONTS);
    }

    public static function getGoogleFontImportUrl(string $fontFamily): ?string
    {
        if (! isset(self::GOOGLE_FONTS[$fontFamily])) {
            return null;
        }

        return 'https://fonts.googleapis.com/css2?family='.self::GOOGLE_FONTS[$fontFamily].'&display=swap';
    }

    public static function getFontFamilyOptions(): array
    {
        $system = [];
        foreach (self::SYSTEM_FONTS as $name => $label) {
            $system[$name] = $label;
        }

        $google = [];
        foreach (self::GOOGLE_FONTS as $name => $spec) {
            $google[$name] = $name;
        }

        return [
            'Sistem' => $system,
            'Google Fonts' => $google,
        ];
    }

    /**
     * Simpan setting global (fasilitas_id = null).
     */
    public static function setGlobal(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            PengaturanLaporan::updateOrCreate(
                ['fasilitas_id' => null, 'grup' => self::GRUP, 'key' => $key],
                ['value' => (string) $value],
            );
        }
    }

    /**
     * Simpan setting per faskes (override).
     */
    public static function setFaskes(int $fasilitasId, array $values): void
    {
        $allowed = ['kop_baris_1', 'kop_baris_2', 'kop_alamat', 'logo_path'];

        foreach ($values as $key => $value) {
            if (! in_array($key, $allowed)) {
                continue;
            }

            PengaturanLaporan::updateOrCreate(
                ['fasilitas_id' => $fasilitasId, 'grup' => self::GRUP, 'key' => $key],
                ['value' => (string) $value],
            );
        }
    }
}
