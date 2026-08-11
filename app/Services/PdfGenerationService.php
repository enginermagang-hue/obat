<?php

namespace App\Services;

use App\Models\DistribusiObat;
use App\Models\LaporanLplpo;
use App\Models\LaporanRko;
use App\Models\NeracaTahunan;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\ReturObat;
use App\Models\SumberDana;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfGenerationService
{
    const TYPE_MAP = [
        'faktur-distribusi' => [
            'model' => DistribusiObat::class,
            'view' => 'pdf.faktur-distribusi',
            'default_format' => 'A4',
            'default_landscape' => false,
        ],
        'faktur-penerimaan' => [
            'model' => PenerimaanStok::class,
            'view' => 'pdf.faktur-penerimaan',
            'default_format' => 'A4',
            'default_landscape' => false,
        ],
        'faktur-permintaan' => [
            'model' => PermintaanObat::class,
            'view' => 'pdf.faktur-permintaan',
            'default_format' => 'A4',
            'default_landscape' => false,
        ],
        'faktur-retur' => [
            'model' => ReturObat::class,
            'view' => 'pdf.faktur-retur',
            'default_format' => 'A4',
            'default_landscape' => false,
        ],
        'lplpo' => [
            'model' => LaporanLplpo::class,
            'view' => 'pdf.lplpo',
            'default_format' => 'A4',
            'default_landscape' => true,
        ],
        'rko' => [
            'model' => LaporanRko::class,
            'view' => 'pdf.rko',
            'default_format' => 'A4',
            'default_landscape' => true,
        ],
        'neraca' => [
            'model' => NeracaTahunan::class,
            'view' => 'pdf.neraca-tahunan',
            'default_format' => 'A4',
            'default_landscape' => true,
        ],
    ];

    public static function getValidTypes(): array
    {
        return array_keys(self::TYPE_MAP);
    }

    public static function getModelClass(string $type): string
    {
        return self::TYPE_MAP[$type]['model'];
    }

    public static function getDefaultOrientation(string $type): string
    {
        return self::TYPE_MAP[$type]['default_landscape'] ? 'landscape' : 'portrait';
    }

    public static function generate(string $type, int $recordId, array $overrides = []): string
    {
        $config = self::TYPE_MAP[$type];
        $model = $config['model']::findOrFail($recordId);

        $faskesId = self::resolveFaskesId($type, $model);
        $kop = PdfSettingsService::getKopSurat($faskesId);
        $layout = PdfSettingsService::getLayoutWithOverrides($overrides);
        $settings = PdfSettingsService::getSettings($faskesId);

        $viewData = self::buildViewData($type, $model, $kop, $layout, $settings);

        $format = $overrides['paper_format'] ?? $config['default_format'];
        $orientation = $overrides['orientation'] ?? self::getDefaultOrientation($type);
        $landscape = $orientation === 'landscape';

        $pdfBuilder = Pdf::view($config['view'], $viewData)
            ->format($format);

        if ($landscape) {
            $pdfBuilder->landscape();
        }

        return base64_decode($pdfBuilder->base64());
    }

    public static function generateFilename(string $type, $model): string
    {
        return match ($type) {
            'faktur-distribusi' => "faktur-distribusi-{$model->nomor_surat_jalan}.pdf",
            'faktur-penerimaan' => 'faktur-penerimaan-'.str_replace('/', '_', $model->nomor_penerimaan).'.pdf',
            'faktur-permintaan' => 'permintaan-obat-'.str_replace('/', '_', $model->nomor_permintaan).'.pdf',
            'faktur-retur' => 'faktur-retur-'.str_replace('/', '_', $model->nomor_retur).'.pdf',
            'lplpo' => "lplpo-{$model->nomor_laporan}-{$model->periode_tahun}-{$model->periode_bulan}.pdf",
            'rko' => "rko-{$model->nomor_rko}-{$model->periode_tahun}.pdf",
            'neraca' => "neraca-tahunan-{$model->nomor_neraca}-{$model->tahun}.pdf",
            default => 'document.pdf',
        };
    }

    private static function resolveFaskesId(string $type, $model): ?int
    {
        return match ($type) {
            'faktur-distribusi' => $model->fasilitasPengirim?->id,
            'faktur-penerimaan' => $model->fasilitas?->id,
            'faktur-permintaan' => $model->fasilitasPengirim?->id,
            'faktur-retur' => $model->fasilitasPengirim?->id,
            'lplpo' => $model->fasilitas?->id,
            'rko' => $model->fasilitas?->id,
            'neraca' => $model->fasilitas?->id,
            default => null,
        };
    }

    private static function buildViewData(string $type, $model, array $kop, array $layout, array $settings): array
    {
        $bodySize = intval($layout['font_size_body']);
        $itemsSize = max(8, $bodySize - 2);

        $base = [
            'kop' => $kop,
            'layout' => $layout,
            'pdfItemsSize' => $itemsSize,
            'googleFontUrl' => PdfSettingsService::isGoogleFont($layout['font_family'])
                ? PdfSettingsService::getGoogleFontImportUrl($layout['font_family'])
                : null,
        ];

        return match ($type) {
            'faktur-distribusi' => array_merge($base, [
                'distribusi' => $model->load([
                    'fasilitasPengirim',
                    'fasilitasPenerima',
                    'details.obat',
                    'details.batch',
                    'pengirim',
                ]),
            ]),
            'faktur-penerimaan' => array_merge($base, [
                'penerimaan' => $model->load([
                    'details.obat',
                    'fasilitas',
                    'supplier',
                    'user',
                    'sumberDana',
                    'distribusi.fasilitasPengirim',
                ]),
                'totalQuantity' => $model->details->sum('jumlah'),
            ]),
            'faktur-permintaan' => array_merge($base, [
                'permintaan' => $model->load([
                    'details.obat',
                    'fasilitasPengirim',
                    'fasilitasTujuan',
                    'disetujuiOleh',
                    'distribusi',
                ]),
            ]),
            'faktur-retur' => array_merge($base, [
                'retur' => $model->load([
                    'details.obat',
                    'details.batch',
                    'fasilitasPengirim',
                    'fasilitasPenerima',
                    'supplier',
                    'disetujuiOleh',
                ]),
            ]),
            'lplpo' => array_merge($base, [
                'laporan' => $model,
                'details' => $model->details()->with('obat')->get(),
            ]),
            'rko' => array_merge($base, [
                'laporan' => $model->load(['details.obat', 'fasilitas', 'dibuatOleh', 'disetujuiOleh']),
                'details' => $model->details,
            ]),
            'neraca' => array_merge($base, [
                'neraca' => $model,
                'details' => $model->details()->with(['obat', 'sumberDanaDetails.sumberDana'])->get(),
                'sumberDanaList' => self::getNeracaSumberDanaList($model),
                'settings' => $settings,
            ]),
            default => $base,
        };
    }

    private static function getNeracaSumberDanaList(NeracaTahunan $neraca)
    {
        return SumberDana::whereIn('id', function ($q) use ($neraca) {
            $q->select('sumber_dana_id')
                ->from('detail_neraca_sumber_dana')
                ->join('detail_neraca_tahunan', 'detail_neraca_sumber_dana.detail_neraca_id', '=', 'detail_neraca_tahunan.id')
                ->where('detail_neraca_tahunan.neraca_id', $neraca->id)
                ->distinct();
        })->where('tahun', $neraca->tahun)->orderBy('kode')->get()->values();
    }
}
