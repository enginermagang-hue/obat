<?php

namespace App\Services;

use App\Models\PengaturanLaporan;
use Illuminate\Support\Facades\Storage;

class PdfSettingsService
{
    const GRUP = 'pdf';

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

    const DEFAULT_LAYOUT = [
        'paper_format' => 'A4',
        'orientation' => 'portrait',
        'font_family' => 'DejaVu Sans',
        'font_size_kop1' => '14',
        'font_size_kop2' => '16',
        'font_size_body' => '12',
        'margin_top' => '18',
        'margin_bottom' => '25',
        'margin_left' => '18',
        'margin_right' => '18',
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

        if ($logoPath) {
            if (str_contains($logoPath, '://') || str_starts_with($logoPath, 'data:')) {
                $logoSrc = $logoPath;
            } elseif (str_starts_with($logoPath, '/')) {
                if (file_exists($logoPath)) {
                    $mime = mime_content_type($logoPath) ?: 'image/png';
                    $data = file_get_contents($logoPath);
                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($data);
                }
            } else {
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
}
