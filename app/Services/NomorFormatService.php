<?php

namespace App\Services;

use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\LaporanRko;
use App\Models\NeracaTahunan;
use App\Models\OpnameStok;
use App\Models\PemakaianObat;
use App\Models\PenerimaanStok;
use App\Models\PengaturanLaporan;
use App\Models\PermintaanObat;
use App\Models\ReturObat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NomorFormatService
{
    const GRUP = 'format_nomor';

    const DOCUMENTS = [
        'permintaan_obat' => [
            'label' => 'Permintaan Obat',
            'model' => PermintaanObat::class,
            'column' => 'nomor_permintaan',
            'default' => 'RQ/{FASKES}/{YYYY}/{MM}/{Urut:4}',
        ],
        'penerimaan_stok' => [
            'label' => 'Penerimaan Stok',
            'model' => PenerimaanStok::class,
            'column' => 'nomor_penerimaan',
            'default' => 'PO/{FASKES}/{YYYY}/{MM}/{Urut:4}',
        ],
        'retur_obat' => [
            'label' => 'Retur Obat',
            'model' => ReturObat::class,
            'column' => 'nomor_retur',
            'default' => 'RET/{FASKES}/{YYYY}/{Urut:3}',
        ],
        'pemakaian_obat' => [
            'label' => 'Pemakaian Obat',
            'model' => PemakaianObat::class,
            'column' => 'nomor_pemakaian',
            'default' => 'PMK-{FASKES}-{YYYYMM}-{Urut:4}',
        ],
        'opname_stok' => [
            'label' => 'Opname Stok',
            'model' => OpnameStok::class,
            'column' => 'nomor_opname',
            'default' => '{PREFIX}/{FASKES}/{YYYY}/{MM}/{Urut:4}',
        ],
        'distribusi_obat' => [
            'label' => 'Surat Jalan (Distribusi)',
            'model' => DistribusiObat::class,
            'column' => 'nomor_surat_jalan',
            'default' => 'SJ/{FASKES}/{YYYY}/{Urut:3}',
        ],
        'laporan_lplpo' => [
            'label' => 'LPLPO',
            'model' => LaporanLplpo::class,
            'column' => 'nomor_laporan',
            'default' => 'LPLPO-{FASKES}-{YYYYMMDD}-{Urut:3}',
        ],
        'laporan_rko' => [
            'label' => 'RKO',
            'model' => LaporanRko::class,
            'column' => 'nomor_rko',
            'default' => 'RKO-{FASKES}-{YYYYMMDD}-{Urut:3}',
        ],
        'neraca_tahunan' => [
            'label' => 'Neraca Tahunan',
            'model' => NeracaTahunan::class,
            'column' => 'nomor_neraca',
            'default' => 'NR-{FASKES}-{YYYYMMDD}-{Urut:3}',
        ],
    ];

    public static function getPattern(string $docKey): string
    {
        $default = self::DOCUMENTS[$docKey]['default'] ?? '';

        return PengaturanLaporan::whereNull('fasilitas_id')
            ->where('grup', self::GRUP)
            ->where('key', $docKey)
            ->value('value') ?? $default;
    }

    public static function generate(
        string $docKey,
        ?int $fasilitasId = null,
        ?string $date = null,
        array $overrides = [],
    ): string {
        $pattern = self::getPattern($docKey);
        $dateObj = $date ? Carbon::parse($date) : now();

        if (preg_match('/\{Urut:(\d+)\}/', $pattern, $seqMatch)) {
            $digits = (int) $seqMatch[1];
            $seqToken = $seqMatch[0];
            $seqPos = strpos($pattern, $seqToken);
            $beforeSeq = substr($pattern, 0, $seqPos);
            $afterSeq = substr($pattern, $seqPos + strlen($seqToken));

            $resolvedPrefix = self::resolve($beforeSeq, $fasilitasId, $dateObj, $overrides);
            $resolvedSuffix = self::resolve($afterSeq, $fasilitasId, $dateObj, $overrides);

            $modelClass = self::DOCUMENTS[$docKey]['model'];
            $column = self::DOCUMENTS[$docKey]['column'];
            $seqNumber = self::nextSequence($modelClass, $column, $resolvedPrefix, $resolvedSuffix, $digits);

            return $resolvedPrefix.$seqNumber.$resolvedSuffix;
        }

        return self::resolve($pattern, $fasilitasId, $dateObj, $overrides);
    }

    private static function resolve(
        string $text,
        ?int $fasilitasId,
        Carbon $date,
        array $overrides = [],
    ): string {
        $map = [
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{M}' => $date->format('n'),
            '{DD}' => $date->format('d'),
            '{D}' => $date->format('j'),
            '{YYYYMM}' => $date->format('Ym'),
            '{YYYYMMDD}' => $date->format('Ymd'),
        ];

        if (str_contains($text, '{PREFIX}')) {
            $map['{PREFIX}'] = $overrides['PREFIX'] ?? 'DOC';
        }

        if (str_contains($text, '{FASKES}')) {
            if (isset($overrides['FASKES'])) {
                $map['{FASKES}'] = $overrides['FASKES'];
            } elseif ($fasilitasId) {
                $map['{FASKES}'] = FasilitasKesehatan::where('id', $fasilitasId)->value('kode_faskes') ?? 'GUD';
            } else {
                $map['{FASKES}'] = 'GUD';
            }
        }

        return str_replace(array_keys($map), array_values($map), $text);
    }

    private static function nextSequence(
        string $modelClass,
        string $column,
        string $prefix,
        string $suffix,
        int $digits,
    ): string {
        $likePattern = $prefix.'%'.$suffix;

        $next = DB::transaction(function () use ($modelClass, $column, $likePattern, $prefix, $suffix): int {
            $last = $modelClass::query()
                ->where($column, 'like', $likePattern)
                ->orderByDesc($column)
                ->lockForUpdate()
                ->value($column);

            $next = 1;
            if ($last) {
                $seq = substr($last, strlen($prefix));
                if ($suffix !== '') {
                    $seq = substr($seq, 0, -strlen($suffix));
                }
                $next = ((int) $seq) + 1;
            }

            return $next;
        });

        return str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
    }

    public static function preview(string $docKey, ?int $fasilitasId = null, array $overrides = []): string
    {
        return self::generate($docKey, $fasilitasId, null, $overrides);
    }

    public static function documents(): array
    {
        return self::DOCUMENTS;
    }

    public static function setGlobal(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! isset(self::DOCUMENTS[$key])) {
                continue;
            }

            PengaturanLaporan::updateOrCreate(
                ['fasilitas_id' => null, 'grup' => self::GRUP, 'key' => $key],
                ['value' => (string) $value],
            );
        }
    }

    public static function resetToDefaults(): void
    {
        $defaults = [];
        foreach (self::DOCUMENTS as $key => $doc) {
            $defaults[$key] = $doc['default'];
        }

        self::setGlobal($defaults);
    }
}
