<?php

namespace App\Services;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\LaporanRko;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\PenerimaanStok;
use App\Models\StokFaskes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TabulasiImportService
{
    private const BULAN_LABELS = [
        'januari', 'februari', 'maret', 'april', 'mei', 'juni',
        'juli', 'agustus', 'september', 'oktober', 'november', 'desember',
    ];

    private int $adminUserId;

    public function __construct(?int $adminUserId = null)
    {
        $this->adminUserId = $adminUserId ?? 1;
    }

    public function getAvailableFiles(): array
    {
        $dir = base_path('tabulasi');
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir.'/*.xlsx') as $path) {
            $basename = basename($path);
            $name = pathinfo($basename, PATHINFO_FILENAME);
            $name = preg_replace('/^Tabulasi\s+/', '', $name);
            $name = preg_replace('/\s+Fix$/', '', $name);
            $files[$name] = [
                'path' => $path,
                'filename' => $basename,
                'faskes_name' => $name,
            ];
        }

        ksort($files);

        return $files;
    }

    public function parseFile(string $filePath, ?int $overrideFaskesId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $maxCol = $sheet->getHighestColumn();
        $maxColIndex = Coordinate::columnIndexFromString($maxCol);
        $maxRow = $sheet->getHighestRow();

        // Deteksi format prediksi wide terlebih dahulu (minimal header baris 1).
        $prediksiMeta = $this->detectPrediksiWide($sheet, $maxColIndex);
        if ($prediksiMeta !== null) {
            return $this->parsePrediksiWideFile($sheet, $maxRow, $prediksiMeta, $overrideFaskesId);
        }

        $format = $this->detectFormat($maxColIndex);
        $faskesName = $this->extractFaskesName($sheet);

        $obatList = [];
        for ($row = 4; $row <= $maxRow; $row++) {
            $no = $sheet->getCell("A{$row}")->getValue();
            if (blank($no)) {
                continue;
            }

            $kodeObat = trim((string) $sheet->getCell("B{$row}")->getValue());
            if (blank($kodeObat)) {
                continue;
            }

            $obatData = $this->parseObatRow($sheet, $row, $maxColIndex, $format, $kodeObat);
            if ($obatData) {
                $obatList[] = $obatData;
            }
        }

        return [
            'faskes_name' => $faskesName,
            'format' => $format,
            'total_rows' => count($obatList),
            'obat' => $obatList,
        ];
    }

    /**
     * Deteksi format Opsi A — minimal wide: header baris 1 berisi kode_obat + minimal 3 kolom periode YYYY-MM.
     *
     * @return array{col_kode:int,col_nama:int,col_satuan:int,col_stok:int|null,periode_cols:array<int, string>}|null
     */
    private function detectPrediksiWide($sheet, int $maxColIndex): ?array
    {
        $headers = [];
        $rawHeaders = [];
        for ($col = 1; $col <= $maxColIndex; $col++) {
            $cell = $sheet->getCell($this->colLetter($col).'1');
            $raw = $cell->getValue();
            // Handle Excel date-formatted headers that become numeric
            if ($cell->getDataType() === 'n' && is_numeric($raw)) {
                $raw = (string) $raw;
            }
            $val = trim((string) ($raw ?? ''));
            $rawHeaders[$col] = $val;
            $headers[$col] = mb_strtolower($val);
        }

        $hasKode = false;
        $colKode = null;
        $colNama = null;
        $colSatuan = null;
        $colStok = null;
        foreach ($headers as $col => $h) {
            $hNorm = preg_replace('/\s+/', ' ', trim($h));
            $hNorm = str_replace(['-', '_'], ' ', $hNorm);
            $hCompact = str_replace(' ', '', $hNorm);
            if (in_array($h, ['kode_obat', 'kode obat', 'kode'], true) || str_contains($hCompact, 'kodeobat') || str_contains($h, 'kode')) {
                if ($colKode === null) {
                    $colKode = $col;
                    $hasKode = true;
                }
            }
            if (in_array($hNorm, ['nama obat', 'namaobat'], true) || $h === 'nama_obat' || $h === 'nama obat') {
                $colNama = $col;
            }
            if ($hNorm === 'satuan' || $h === 'satuan') {
                $colSatuan = $col;
            }
            if (in_array($hNorm, ['stok akhir', 'stokakhir', 'sisa stok', 'sisastok'], true) || in_array($h, ['stok_akhir', 'stok akhir', 'stok', 'sisa_stok', 'sisa stok'], true)) {
                $colStok = $col;
            }
        }

        if (! $hasKode) {
            return null;
        }

        // Kolom periode: header cocok YYYY-MM, YYYY/MM, YYYY_MM, YYYY.MM, YYYY MM, atau Excel date
        $periodeCols = [];
        foreach ($headers as $col => $h) {
            $raw = $rawHeaders[$col] ?? $h;
            // Excel may store 2024-01 as date 2024-01-01 or as formatted string
            $normalized = trim($raw);
            $normalized = str_replace(['/', '_', '.', ' '], '-', $normalized);
            $normalized = preg_replace('/-+/', '-', $normalized);
            $normalized = trim($normalized, '-');
            // Strip day if YYYY-MM-DD
            if (preg_match('/^(\d{4})-(\d{1,2})-\d{1,2}$/', $normalized, $m)) {
                $normalized = $m[1].'-'.$m[2];
            }
            if (preg_match('/^\d{4}-\d{1,2}$/', $normalized)) {
                [$y, $mVal] = explode('-', $normalized);
                $mVal = (int) $mVal;
                if ($mVal >= 1 && $mVal <= 12) {
                    $periodeCols[$col] = sprintf('%04d-%02d', (int) $y, $mVal);
                }
            }
        }

        if (count($periodeCols) < 3) {
            return null;
        }

        ksort($periodeCols);

        return [
            'col_kode' => $colKode,
            'col_nama' => $colNama,
            'col_satuan' => $colSatuan,
            'col_stok' => $colStok,
            'periode_cols' => $periodeCols,
        ];
    }

    private function parsePrediksiWideFile($sheet, int $maxRow, array $meta, ?int $overrideFaskesId = null): array
    {
        // 1 file = 1 faskes via dropdown; sheet name tidak dipakai jika override ada
        $faskesName = $overrideFaskesId ? (FasilitasKesehatan::find($overrideFaskesId)?->nama ?? 'Faskes #'.$overrideFaskesId) : $this->extractFaskesName($sheet);
        $periodeCols = $meta['periode_cols'];
        $colKode = $meta['col_kode'];
        $colNama = $meta['col_nama'];
        $colSatuan = $meta['col_satuan'];
        $colStok = $meta['col_stok'];

        // Harga opsional: cari header harga
        $colHarga = null;
        $maxColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($col = 1; $col <= $maxColIndex; $col++) {
            $h = mb_strtolower(trim((string) ($sheet->getCell($this->colLetter($col).'1')->getValue() ?? '')));
            if (in_array($h, ['harga', 'harga_satuan', 'harga satuan'], true)) {
                $colHarga = $col;
                break;
            }
        }

        $obatList = [];
        for ($row = 2; $row <= $maxRow; $row++) {
            $kodeObat = trim((string) ($sheet->getCell($this->colLetter($colKode).$row)->getValue() ?? ''));
            if (blank($kodeObat)) {
                continue;
            }

            // Lewati baris yang kode_obat bukan alnum (misal header ulang)
            if (! preg_match('/^[A-Za-z0-9_-]+$/', $kodeObat)) {
                continue;
            }

            $namaObat = $colNama ? trim((string) ($sheet->getCell($this->colLetter($colNama).$row)->getValue() ?? '')) : '';
            $satuan = $colSatuan ? trim((string) ($sheet->getCell($this->colLetter($colSatuan).$row)->getValue() ?? '')) : '';
            $harga = $colHarga ? (float) ($sheet->getCell($this->colLetter($colHarga).$row)->getValue() ?? 0) : 0;

            $periodeMap = [];
            $totalPemakaian = 0;
            foreach ($periodeCols as $col => $periodeKey) {
                $cell = $sheet->getCell($this->colLetter($col).$row);
                $val = $cell->getCalculatedValue() ?? $cell->getValue();
                $jumlah = $this->parseNumericCell($val);
                $periodeMap[$periodeKey] = $jumlah;
                $totalPemakaian += $jumlah;
            }

            $stokAkhir = 0;
            if ($colStok) {
                $cell = $sheet->getCell($this->colLetter($colStok).$row);
                $stokVal = $cell->getCalculatedValue() ?? $cell->getValue();
                $stokAkhir = $this->parseNumericCell($stokVal);
            }

            // Bangun array bulan 1..12 untuk kompatibilitas import single-tahun (tahun dominan).
            $tahunDominan = null;
            if (! empty($periodeMap)) {
                $tahunCounts = [];
                foreach (array_keys($periodeMap) as $k) {
                    $y = (int) explode('-', $k)[0];
                    $tahunCounts[$y] = ($tahunCounts[$y] ?? 0) + 1;
                }
                arsort($tahunCounts);
                $tahunDominan = (int) array_key_first($tahunCounts);
            }

            $bulan = [];
            for ($b = 1; $b <= 12; $b++) {
                $bulan[$b] = ['sa' => 0, 'penerimaan' => 0, 'pemakaian' => 0, 'stok_akhir' => 0];
            }
            foreach ($periodeMap as $key => $jumlah) {
                [$y, $m] = array_map('intval', explode('-', $key));
                if ($tahunDominan !== null && $y === $tahunDominan) {
                    $bulan[$m]['pemakaian'] = $jumlah;
                    // stok_akhir pada bulan terakhir periode untuk tahun dominan
                }
            }
            // Stok akhir bulan 12 dari kolom stok_akhir jika ada
            if ($stokAkhir > 0) {
                $bulan[12]['stok_akhir'] = $stokAkhir;
            }

            $obatList[] = [
                'kode_obat' => $kodeObat,
                'nama_obat' => $namaObat,
                'satuan' => $satuan,
                'harga' => $harga,
                'bulan' => $bulan,
                'periode_map' => $periodeMap,
                'periode_columns' => array_values($periodeCols),
                'tahun_dominan' => $tahunDominan,
                'total_penerimaan' => 0,
                'total_pemakaian' => $totalPemakaian,
                'stok_akhir_des' => $stokAkhir,
                'rko' => 0,
                'pemakaian_bulanan' => $totalPemakaian > 0 ? (int) round($totalPemakaian / max(1, count($periodeMap))) : 0,
            ];
        }

        // Tahun dominan untuk 1 file = 1 tahun (dominan)
        $topTahunDominan = null;
        if (! empty($periodeCols)) {
            $tahunCounts = [];
            foreach (array_values($periodeCols) as $p) {
                $y = (int) explode('-', $p)[0];
                $tahunCounts[$y] = ($tahunCounts[$y] ?? 0) + 1;
            }
            arsort($tahunCounts);
            $topTahunDominan = (int) array_key_first($tahunCounts);
        }

        return [
            'faskes_name' => $faskesName,
            'override_faskes_id' => $overrideFaskesId,
            'format' => 'prediksi_wide',
            'total_rows' => count($obatList),
            'obat' => $obatList,
            'periode_columns' => array_values($periodeCols),
            'tahun_dominan' => $topTahunDominan,
        ];
    }

    public function validateData(array $data, ?int $fasilitasId = null, ?int $tahun = null): array
    {
        $errors = [];
        $warnings = [];

        // Jika dropdown dipilih, faskes wajib ada & tidak auto-create
        if ($fasilitasId !== null) {
            $faskes = FasilitasKesehatan::find($fasilitasId);
            if (! $faskes) {
                $errors[] = "Fasilitas dengan ID {$fasilitasId} tidak ditemukan. Pilih faskes yang valid.";
            }
        } else {
            $faskesName = $data['faskes_name'] ?? 'Unknown';
            $faskes = FasilitasKesehatan::where('nama', 'like', "%{$faskesName}%")->first();
            if (! $faskes) {
                $errors[] = "Faskes '{$faskesName}' tidak ditemukan. Pilih faskes dari dropdown (auto-create dimatikan).";
            }
        }

        $obatCodes = collect($data['obat'])->pluck('kode_obat')->unique()->values();
        $existingObat = Obat::whereIn('kode_obat', $obatCodes)->pluck('id', 'kode_obat');
        $missing = $obatCodes->diff($existingObat->keys());

        if ($missing->isNotEmpty()) {
            $errors[] = 'Kode obat tidak ditemukan: '.$missing->implode(', ');
        }

        if (($data['format'] ?? '') === 'prediksi_wide') {
            $periodeCount = count($data['periode_columns'] ?? []);
            if ($periodeCount < 6) {
                $warnings[] = "Format prediksi_wide terdeteksi dengan {$periodeCount} periode. Minimal 6 bulan untuk AI Gradient Boost, 3 bulan untuk fallback Moving Average. Tambah kolom periode YYYY-MM.";
            } elseif ($periodeCount < 12) {
                $warnings[] = "Hanya {$periodeCount} periode terdeteksi. Untuk akurasi optimal, gunakan 12 bulan.";
            }

            // Validasi tahun: ideal 1 tahun, tapi izinkan lintas 2 tahun untuk 12 bulan terakhir (mis. 2025-07 s/d 2026-06).
            if ($tahun !== null && ! empty($data['periode_columns'])) {
                $tahunList = collect($data['periode_columns'])->map(fn ($p) => (int) explode('-', $p)[0])->unique()->sort()->values();
                if ($tahunList->count() > 2) {
                    $errors[] = 'Template mengandung lebih dari 2 tahun ('.implode(', ', $tahunList->all()).'). Maksimal lintas 2 tahun untuk 12 bulan terakhir. Pisah menjadi 2 file atau sesuaikan header YYYY-MM.';
                } elseif ($tahunList->count() === 2) {
                    if (! $tahunList->contains((int) $tahun)) {
                        $errors[] = 'Tahun terpilih ('.$tahun.') tidak ada di header ('.implode(', ', $tahunList->all()).'). Pilih Tahun yang ada di header atau sesuaikan kolom YYYY-MM.';
                    } else {
                        $warnings[] = 'Template lintas 2 tahun ('.implode(', ', $tahunList->all()).') — akan diimpor semua periode; pastikan 12 bulan terakhir berurutan.';
                    }
                } elseif ($tahunList->count() === 1 && (int) $tahunList->first() !== (int) $tahun) {
                    $errors[] = 'Tahun pada header ('.$tahunList->first().') tidak sesuai dengan Tahun terpilih ('.$tahun.'). Ubah header YYYY-MM atau pilih Tahun yang benar.';
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'faskes_exists' => (bool) $faskes,
            'faskes_id' => $faskes?->id,
            'obat_matched' => $existingObat->count(),
            'obat_missing' => $missing->count(),
        ];
    }

    public function import(array $data, array $options): array
    {
        $targets = $options['targets'] ?? [];
        $tahun = $options['tahun'] ?? 2024;
        $dryRun = $options['dry_run'] ?? false;
        // 1 file = 1 faskes via dropdown — tidak auto-create
        $overrideFaskesId = $options['fasilitas_id'] ?? $data['override_faskes_id'] ?? null;

        $result = [
            'faskes_name' => $data['faskes_name'] ?? 'Unknown',
            'dry_run' => $dryRun,
            'targets' => [],
            'errors' => [],
        ];

        // Validasi silang tahun vs header periode untuk prediksi_wide — cegah silent 0 pemakaian.
        // Izinkan lintas 2 tahun (12 bulan terakhir), maksimal 2 tahun berbeda.
        if (($data['format'] ?? '') === 'prediksi_wide' && ! empty($data['periode_columns'])) {
            $tahunList = collect($data['periode_columns'])->map(fn ($p) => (int) explode('-', $p)[0])->unique()->sort()->values();
            if ($tahunList->count() > 2) {
                $result['errors'][] = 'Template mengandung lebih dari 2 tahun ('.implode(', ', $tahunList->all()).'). Maksimal lintas 2 tahun untuk 12 bulan terakhir.';

                return $result;
            }
            if ($tahunList->count() === 1 && (int) $tahunList->first() !== (int) $tahun) {
                $result['errors'][] = 'Tahun pada header ('.$tahunList->first().') tidak sesuai dengan Tahun terpilih ('.$tahun.'). Ubah header YYYY-MM atau pilih Tahun yang benar.';

                return $result;
            }
            if ($tahunList->count() === 2 && ! $tahunList->contains((int) $tahun)) {
                $result['errors'][] = 'Tahun terpilih ('.$tahun.') tidak ada di header ('.implode(', ', $tahunList->all()).'). Pilih Tahun yang ada di header.';

                return $result;
            }
        }

        $faskesId = null;
        if ($overrideFaskesId) {
            $faskes = FasilitasKesehatan::find($overrideFaskesId);
            if (! $faskes) {
                $result['errors'][] = "Faskes ID {$overrideFaskesId} tidak ditemukan. Pilih faskes dari dropdown.";

                return $result;
            }
            $faskesId = $faskes->id;
            $result['faskes_name'] = $faskes->nama;
        } else {
            // Fallback untuk file legacy tanpa dropdown — tetap tanpa auto-create
            $faskesName = $data['faskes_name'] ?? 'Unknown';
            $faskes = FasilitasKesehatan::where('nama', 'like', "%{$faskesName}%")->first();
            if (! $faskes) {
                $result['errors'][] = "Faskes '{$faskesName}' tidak ditemukan. Pilih faskes dari dropdown (auto-create dimatikan).";

                return $result;
            }
            $faskesId = $faskes->id;
        }

        $obatMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('id', 'kode_obat');

        $obatVenMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('ven_kategori', 'kode_obat');

        $obatHargaMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('harga_satuan', 'kode_obat');

        if (in_array('pemakaian', $targets) && $obatMap->isEmpty() && ! empty($data['obat'])) {
            $sampleKode = collect($data['obat'])->pluck('kode_obat')->take(3)->implode(', ');
            $result['errors'][] = "Tidak ada kode_obat yang cocok dengan master obat. Contoh kode di file: {$sampleKode}. Periksa master obat atau format kode (tanpa spasi, tanpa leading zero hilang).";

            return $result;
        }

        try {
            DB::beginTransaction();

            if (in_array('stok_faskes', $targets)) {
                $result['targets']['stok_faskes'] = $this->importStokFaskes($data, $faskesId, $obatMap, $dryRun);
            }

            if (in_array('lplpo', $targets)) {
                $result['targets']['lplpo'] = $this->importLplpo($data, $faskesId, $obatMap, $tahun, $dryRun);
            }

            if (in_array('rko', $targets)) {
                $result['targets']['rko'] = $this->importRko($data, $faskesId, $obatMap, $obatVenMap, $obatHargaMap, $tahun, $dryRun);
            }

            if (in_array('penerimaan', $targets)) {
                $result['targets']['penerimaan'] = $this->importPenerimaan($data, $faskesId, $obatMap, $obatHargaMap, $tahun, $dryRun);
            }

            if (in_array('pemakaian', $targets)) {
                $result['targets']['pemakaian'] = $this->importPemakaian($data, $faskesId, $obatMap, $tahun, $dryRun);
                $pemakaianResult = $result['targets']['pemakaian'];
                $totalPemakaianFile = collect($data['obat'])->sum(fn ($o) => $this->getTotalPemakaianForTahun($o, $tahun));
                $matchedObat = $obatMap->count();
                $fileObat = count($data['obat']);
                if (($pemakaianResult['reports'] ?? 0) === 0) {
                    if ($totalPemakaianFile > 0) {
                        if ($matchedObat === 0) {
                            $result['errors'][] = "Pemakaian tidak ter-import: 0 obat cocok (file {$fileObat} baris, 0 matched). Periksa kode_obat master.";
                        } else {
                            $result['errors'][] = "Pemakaian tidak ter-import: total pemakaian {$totalPemakaianFile} untuk tahun {$tahun} terdeteksi di file ({$matchedObat}/{$fileObat} obat cocok), tapi 0 laporan dibuat. Cek Tahun terpilih vs header YYYY-MM (file: ".implode(', ', $data['periode_columns'] ?? []).').';
                        }
                    } elseif ($matchedObat > 0) {
                        // Semua nilai periode 0 — beri warning eksplisit bukan error
                        $result['errors'][] = "Pemakaian 0: semua nilai periode YYYY-MM untuk tahun {$tahun} adalah 0 (file: ".implode(', ', array_slice($data['periode_columns'] ?? [], 0, 3)).'...). Isi jumlah pemakaian di kolom periode.';
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $result['errors'][] = $e->getMessage();
            report($e);
        }

        return $result;
    }

    private function detectFormat(int $colCount): string
    {
        if ($colCount >= 80) {
            return 'full';
        }
        if ($colCount >= 70) {
            return 'simple';
        }

        return 'annual';
    }

    private function extractFaskesName($sheet): string
    {
        $sheetName = $sheet->getTitle();
        if ($sheetName && ! in_array($sheetName, ['Lembar1', 'Sheet1'])) {
            return $sheetName;
        }

        return 'Unknown';
    }

    private function parseObatRow($sheet, int $row, int $maxCol, string $format, string $kodeObat): ?array
    {
        $namaObat = trim((string) $sheet->getCell("C{$row}")->getValue());
        $satuan = trim((string) $sheet->getCell("D{$row}")->getValue());
        $harga = (float) $sheet->getCell("E{$row}")->getValue();

        $data = [
            'kode_obat' => $kodeObat,
            'nama_obat' => $namaObat,
            'satuan' => $satuan,
            'harga' => $harga,
            'bulan' => [],
        ];

        if ($format === 'annual') {
            $data = array_merge($data, $this->parseAnnualColumns($sheet, $row));
        } else {
            $data = array_merge($data, $this->parseMonthlyColumns($sheet, $row, $format));
        }

        return $data;
    }

    private function parseMonthlyColumns($sheet, int $row, string $format): array
    {
        $colsPerMonth = $format === 'full' ? 5 : 4;
        $startCol = 6;
        $monthly = [];
        $totalPenerimaan = 0;
        $totalPemakaian = 0;

        for ($i = 0; $i < 12; $i++) {
            $col = $startCol + ($i * $colsPerMonth);
            $colLetter = $this->colLetter($col);
            $colLetter2 = $this->colLetter($col + 1);
            $colLetter3 = $this->colLetter($col + ($colsPerMonth - 2));
            $colLetter4 = $this->colLetter($col + ($colsPerMonth - 1));

            $sa = (int) ($sheet->getCell("{$colLetter}{$row}")->getValue() ?? 0);
            $penerimaan = (int) ($sheet->getCell("{$colLetter2}{$row}")->getValue() ?? 0);
            $pemakaian = (int) ($sheet->getCell("{$colLetter3}{$row}")->getValue() ?? 0);
            $stokAkhir = (int) ($sheet->getCell("{$colLetter4}{$row}")->getValue() ?? 0);

            $monthly[$i + 1] = [
                'sa' => $sa,
                'penerimaan' => $penerimaan,
                'pemakaian' => $pemakaian,
                'stok_akhir' => $stokAkhir,
            ];

            $totalPenerimaan += $penerimaan;
            $totalPemakaian += $pemakaian;
        }

        $summary = $this->parseSummaryColumns($sheet, $row, $format);

        return [
            'bulan' => $monthly,
            'total_penerimaan' => $totalPenerimaan,
            'total_pemakaian' => $totalPemakaian,
            'stok_akhir_des' => $monthly[12]['stok_akhir'] ?? 0,
            'rko' => $summary['rko'] ?? 0,
            'pemakaian_bulanan' => $summary['pemakaian_bulanan'] ?? 0,
        ];
    }

    private function parseAnnualColumns($sheet, int $row): array
    {
        $sa = (int) ($sheet->getCell("F{$row}")->getValue() ?? 0);
        $penerimaan = (int) ($sheet->getCell("G{$row}")->getValue() ?? 0);
        $pemakaian = (int) ($sheet->getCell("I{$row}")->getValue() ?? 0);
        $stokAkhir = (int) ($sheet->getCell("J{$row}")->getValue() ?? 0);

        $totalStokAwal = (int) ($sheet->getCell("K{$row}")->getValue() ?? 0);
        $totalPenerimaan = (int) ($sheet->getCell("M{$row}")->getValue() ?? 0);
        $totalPemakaian = (int) ($sheet->getCell("N{$row}")->getValue() ?? 0);
        $totalStokAkhir = (int) ($sheet->getCell("O{$row}")->getValue() ?? 0);
        $pemakaianBulanan = (int) ($sheet->getCell("Q{$row}")->getValue() ?? 0);
        $rko = (int) ($sheet->getCell("T{$row}")->getValue() ?? 0);

        $monthly = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthly[$i] = [
                'sa' => $i === 1 ? $sa : 0,
                'penerimaan' => $i === 1 ? $penerimaan : 0,
                'pemakaian' => $i === 1 ? $pemakaian : 0,
                'stok_akhir' => $i === 12 ? $stokAkhir : 0,
            ];
        }

        return [
            'bulan' => $monthly,
            'total_penerimaan' => $totalPenerimaan,
            'total_pemakaian' => $totalPemakaian,
            'stok_akhir_des' => $totalStokAkhir,
            'rko' => $rko,
            'pemakaian_bulanan' => $pemakaianBulanan,
        ];
    }

    private function parseSummaryColumns($sheet, int $row, string $format): array
    {
        if ($format === 'full') {
            return [
                'rko' => (int) ($sheet->getCell("BY{$row}")->getValue() ?? 0),
                'pemakaian_bulanan' => (int) ($sheet->getCell("BU{$row}")->getValue() ?? 0),
            ];
        }

        return [
            'rko' => (int) ($sheet->getCell("BM{$row}")->getValue() ?? 0),
            'pemakaian_bulanan' => (int) ($sheet->getCell("BI{$row}")->getValue() ?? 0),
        ];
    }

    private function resolveFaskesId(string $faskesName, bool $autoCreate, bool $dryRun): ?int
    {
        $faskes = FasilitasKesehatan::where('nama', 'like', "%{$faskesName}%")->first();

        if ($faskes) {
            return $faskes->id;
        }

        if (! $autoCreate) {
            return null;
        }

        if ($dryRun) {
            return -1;
        }

        $faskes = FasilitasKesehatan::create([
            'nama' => "Puskesmas {$faskesName}",
            'kode_faskes' => strtoupper(substr($faskesName, 0, 4)).rand(100, 999),
            'tipe' => 'puskesmas',
            'status' => 'aktif',
        ]);

        return $faskes->id;
    }

    private function importStokFaskes(array $data, int $faskesId, $obatMap, bool $dryRun): array
    {
        $count = 0;
        foreach ($data['obat'] as $obat) {
            $obatId = $obatMap[$obat['kode_obat']] ?? null;
            if (! $obatId) {
                continue;
            }

            if (! $dryRun) {
                StokFaskes::updateOrCreate(
                    ['fasilitas_id' => $faskesId, 'obat_id' => $obatId],
                    ['jumlah' => max(0, $obat['stok_akhir_des']), 'stok_minimum' => 0]
                );
            }
            $count++;
        }

        return ['count' => $count, 'action' => 'upsert'];
    }

    private function importLplpo(array $data, int $faskesId, $obatMap, int $tahun, bool $dryRun): array
    {
        $reportsCreated = 0;
        $detailsCreated = 0;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $exists = LaporanLplpo::where('fasilitas_id', $faskesId)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $tahun)
                ->exists();

            if ($exists || $dryRun) {
                $reportsCreated++;
                $detailsCreated += count($data['obat']);

                continue;
            }

            $nomor = 'LPLPO-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $lplpo = LaporanLplpo::create([
                'nomor_laporan' => $nomor,
                'fasilitas_id' => $faskesId,
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
                'status' => 'selesai',
                'tanggal_pembuatan' => Carbon::createFromDate($tahun, $bulan, 15)->toDateString(),
                'dibuat_oleh' => $this->adminUserId,
            ]);

            foreach ($data['obat'] as $obat) {
                $obatId = $obatMap[$obat['kode_obat']] ?? null;
                if (! $obatId) {
                    continue;
                }

                $bulanData = $obat['bulan'][$bulan] ?? ['sa' => 0, 'penerimaan' => 0, 'pemakaian' => 0, 'stok_akhir' => 0];
                $lplpo->details()->create([
                    'obat_id' => $obatId,
                    'stok_awal' => max(0, $bulanData['sa']),
                    'jumlah_masuk' => max(0, $bulanData['penerimaan']),
                    'jumlah_keluar' => max(0, $bulanData['pemakaian']),
                    'sisa_stok' => max(0, $bulanData['stok_akhir']),
                    'stok_optimum' => 0,
                    'permintaan_selanjutnya' => 0,
                ]);
                $detailsCreated++;
            }

            $reportsCreated++;
        }

        return ['reports' => $reportsCreated, 'details' => $detailsCreated];
    }

    private function importRko(array $data, int $faskesId, $obatMap, $obatVenMap, $obatHargaMap, int $tahun, bool $dryRun): array
    {
        $exists = LaporanRko::where('fasilitas_id', $faskesId)
            ->where('periode_tahun', $tahun)
            ->exists();

        if ($exists && ! $dryRun) {
            return ['skipped' => true, 'reason' => 'RKO sudah ada untuk tahun '.$tahun];
        }

        $totalAnggaran = 0;
        $detailCount = 0;
        $details = [];

        foreach ($data['obat'] as $obat) {
            $obatId = $obatMap[$obat['kode_obat']] ?? null;
            if (! $obatId) {
                continue;
            }

            $ven = $obatVenMap[$obat['kode_obat']] ?? null;
            $bufferPersen = match ($ven) {
                'V' => 30.0,
                'E' => 20.0,
                'N' => 10.0,
                default => 15.0,
            };

            $harga = (float) ($obatHargaMap[$obat['kode_obat']] ?? $obat['harga'] ?? 0);
            $usulan = max(0, (int) ($obat['rko'] ?? 0));
            $pemakaianTahunLalu = $this->getTotalPemakaianForTahun($obat, $tahun);
            $rataRata = (int) round($pemakaianTahunLalu / 12);
            $stokAkhir = max(0, $obat['stok_akhir_des']);
            $kebutuhan = $rataRata * 18;
            $rencana = max(0, $kebutuhan - $stokAkhir);
            $bufferQty = (int) round($rencana * $bufferPersen / 100);
            $totalKebutuhan = $rencana + $bufferQty;
            $totalHarga = $usulan * $harga;
            $totalAnggaran += $totalHarga;

            $details[] = [
                'obat_id' => $obatId,
                'pemakaian_tahun_sebelumnya' => $pemakaianTahunLalu,
                'rata_rata_pemakaian_bulanan' => $rataRata,
                'stok_akhir' => $stokAkhir,
                'kebutuhan_tahunan' => $kebutuhan,
                'rencana_kebutuhan' => $rencana,
                'usulan' => $usulan,
                'buffer_stock_persen' => $bufferPersen,
                'buffer_stok_qty' => $bufferQty,
                'total_kebutuhan' => $totalKebutuhan,
                'ven_kategori' => $ven,
                'harga_perkiraan' => $harga,
                'total_harga' => $totalHarga,
            ];
            $detailCount++;
        }

        if ($dryRun) {
            return ['reports' => 1, 'details' => $detailCount, 'total_anggaran' => $totalAnggaran, 'dry_run' => true];
        }

        $rko = LaporanRko::create([
            'nomor_rko' => 'RKO-'.date('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT),
            'fasilitas_id' => $faskesId,
            'periode_tahun' => $tahun,
            'status' => 'disetujui',
            'tanggal_pembuatan' => now()->toDateString(),
            'total_anggaran' => $totalAnggaran,
            'dibuat_oleh' => $this->adminUserId,
        ]);

        foreach ($details as $detail) {
            $rko->details()->create($detail);
        }

        return ['reports' => 1, 'details' => $detailCount, 'total_anggaran' => $totalAnggaran];
    }

    private function importPenerimaan(array $data, int $faskesId, $obatMap, $obatHargaMap, int $tahun, bool $dryRun): array
    {
        $reportsCreated = 0;
        $detailsCreated = 0;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $adaPenerimaan = false;
            foreach ($data['obat'] as $obat) {
                if (($obat['bulan'][$bulan]['penerimaan'] ?? 0) > 0) {
                    $adaPenerimaan = true;
                    break;
                }
            }

            if (! $adaPenerimaan) {
                continue;
            }

            if ($dryRun) {
                $reportsCreated++;
                foreach ($data['obat'] as $obat) {
                    if (($obat['bulan'][$bulan]['penerimaan'] ?? 0) > 0) {
                        $detailsCreated++;
                    }
                }

                continue;
            }

            $lastDay = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
            $penerimaan = PenerimaanStok::create([
                'nomor_penerimaan' => 'TRM-'.date('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'tipe' => 'pembelian',
                'fasilitas_id' => $faskesId,
                'tanggal_penerimaan' => Carbon::createFromDate($tahun, $bulan, $lastDay)->toDateString(),
                'user_id' => $this->adminUserId,
                'status' => 'dikonfirmasi',
            ]);

            foreach ($data['obat'] as $obat) {
                $jml = $obat['bulan'][$bulan]['penerimaan'] ?? 0;
                if ($jml <= 0) {
                    continue;
                }

                $obatId = $obatMap[$obat['kode_obat']] ?? null;
                if (! $obatId) {
                    continue;
                }

                $harga = (float) ($obatHargaMap[$obat['kode_obat']] ?? $obat['harga'] ?? 0);
                $penerimaan->details()->create([
                    'obat_id' => $obatId,
                    'jumlah' => $jml,
                    'harga_satuan' => $harga,
                    'sub_total' => $jml * $harga,
                    'tanggal_expired' => Carbon::createFromDate($tahun + 2, $bulan, 1)->toDateString(),
                ]);
                $detailsCreated++;
            }

            $reportsCreated++;
        }

        return ['reports' => $reportsCreated, 'details' => $detailsCreated];
    }

    private function getPemakaianForMonth(array $obat, int $tahun, int $bulan): int
    {
        if (isset($obat['periode_map'])) {
            $key = sprintf('%04d-%02d', $tahun, $bulan);

            return max(0, (int) ($obat['periode_map'][$key] ?? 0));
        }

        return max(0, (int) ($obat['bulan'][$bulan]['pemakaian'] ?? 0));
    }

    private function getTotalPemakaianForTahun(array $obat, int $tahun): int
    {
        if (isset($obat['periode_map'])) {
            $total = 0;
            foreach ($obat['periode_map'] as $key => $val) {
                if (str_starts_with($key, sprintf('%04d-', $tahun))) {
                    $total += max(0, (int) $val);
                }
            }

            return $total;
        }

        return max(0, (int) ($obat['total_pemakaian'] ?? 0));
    }

    private function importPemakaian(array $data, int $faskesId, $obatMap, int $tahun, bool $dryRun): array
    {
        $reportsCreated = 0;
        $detailsCreated = 0;

        // Prediksi wide: iterate over actual YYYY-MM periods (supports lintasan 2 tahun, e.g. 2025-07 s/d 2026-06)
        if (($data['format'] ?? '') === 'prediksi_wide' && ! empty($data['periode_columns'])) {
            $periods = collect($data['periode_columns'])->unique()->sort()->values()->all();

            foreach ($periods as $period) {
                if (! preg_match('/^(\d{4})-(\d{1,2})$/', $period, $m)) {
                    continue;
                }

                $y = (int) $m[1];
                $mo = (int) $m[2];

                $adaPemakaian = false;
                foreach ($data['obat'] as $obat) {
                    if ((int) ($obat['periode_map'][$period] ?? 0) > 0) {
                        $adaPemakaian = true;
                        break;
                    }
                }

                if (! $adaPemakaian) {
                    continue;
                }

                if ($dryRun) {
                    $reportsCreated++;
                    foreach ($data['obat'] as $obat) {
                        if ((int) ($obat['periode_map'][$period] ?? 0) > 0) {
                            $detailsCreated++;
                        }
                    }

                    continue;
                }

                $lastDay = cal_days_in_month(CAL_GREGORIAN, $mo, $y);
                $pemakaian = PemakaianObat::create([
                    'nomor_pemakaian' => 'PMK-'.date('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'fasilitas_id' => $faskesId,
                    'tanggal_pemakaian' => Carbon::createFromDate($y, $mo, $lastDay)->toDateString(),
                    'jenis_pelayanan' => 'lainnya',
                    'user_id' => $this->adminUserId,
                ]);

                foreach ($data['obat'] as $obat) {
                    $jml = (int) ($obat['periode_map'][$period] ?? 0);
                    if ($jml <= 0) {
                        continue;
                    }

                    $obatId = $obatMap[$obat['kode_obat']] ?? null;
                    if (! $obatId) {
                        continue;
                    }

                    $pemakaian->details()->create([
                        'obat_id' => $obatId,
                        'jumlah' => $jml,
                    ]);
                    $detailsCreated++;
                }

                $reportsCreated++;
            }

            return ['reports' => $reportsCreated, 'details' => $detailsCreated];
        }

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $adaPemakaian = false;
            foreach ($data['obat'] as $obat) {
                if ($this->getPemakaianForMonth($obat, $tahun, $bulan) > 0) {
                    $adaPemakaian = true;
                    break;
                }
            }

            if (! $adaPemakaian) {
                continue;
            }

            if ($dryRun) {
                $reportsCreated++;
                foreach ($data['obat'] as $obat) {
                    if ($this->getPemakaianForMonth($obat, $tahun, $bulan) > 0) {
                        $detailsCreated++;
                    }
                }

                continue;
            }

            $lastDay = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
            $pemakaian = PemakaianObat::create([
                'nomor_pemakaian' => 'PMK-'.date('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'fasilitas_id' => $faskesId,
                'tanggal_pemakaian' => Carbon::createFromDate($tahun, $bulan, $lastDay)->toDateString(),
                'jenis_pelayanan' => 'lainnya',
                'user_id' => $this->adminUserId,
            ]);

            foreach ($data['obat'] as $obat) {
                $jml = $this->getPemakaianForMonth($obat, $tahun, $bulan);
                if ($jml <= 0) {
                    continue;
                }

                $obatId = $obatMap[$obat['kode_obat']] ?? null;
                if (! $obatId) {
                    continue;
                }

                $pemakaian->details()->create([
                    'obat_id' => $obatId,
                    'jumlah' => $jml,
                ]);
                $detailsCreated++;
            }

            $reportsCreated++;
        }

        return ['reports' => $reportsCreated, 'details' => $detailsCreated];
    }

    private function colLetter(int $colIndex): string
    {
        return Coordinate::stringFromColumnIndex($colIndex);
    }

    private function parseNumericCell(mixed $val): int
    {
        if ($val === null || $val === '') {
            return 0;
        }
        if (is_numeric($val)) {
            return max(0, (int) $val);
        }
        // Handle strings like "1.000", "1,000", " 10 ", "10.5"
        $str = trim((string) $val);
        if ($str === '') {
            return 0;
        }
        // Remove thousand separators, keep decimal point comma handling
        // "1.000" (ID thousand) -> 1000, "1,000.5" -> 1000.5
        $str = str_replace([' ', "\xc2\xa0"], '', $str); // nbsp
        if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d+$/', $str)) {
            // e.g. 1.234,56 -> 1234.56
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $str)) {
            $str = str_replace(',', '', $str);
        } elseif (str_contains($str, ',') && ! str_contains($str, '.')) {
            // "1,5" could be decimal comma, but pemakaian is integer — treat comma as thousand or decimal
            // If single comma with 1-2 digits after, treat as decimal; otherwise remove
            if (preg_match('/^-?\d+,\d{1,2}$/', $str)) {
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        }
        if (is_numeric($str)) {
            return max(0, (int) (float) $str);
        }

        return 0;
    }
}
