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

    public function parseFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $maxCol = $sheet->getHighestColumn();
        $maxColIndex = Coordinate::columnIndexFromString($maxCol);
        $maxRow = $sheet->getHighestRow();

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

    public function validateData(array $data): array
    {
        $errors = [];
        $warnings = [];

        $faskesName = $data['faskes_name'];
        $faskes = FasilitasKesehatan::where('nama', 'like', "%{$faskesName}%")->first();

        if (! $faskes) {
            $warnings[] = "Faskes '{$faskesName}' belum ada di database. Akan dibuat otomatis.";
        }

        $obatCodes = collect($data['obat'])->pluck('kode_obat')->unique()->values();
        $existingObat = Obat::whereIn('kode_obat', $obatCodes)->pluck('id', 'kode_obat');
        $missing = $obatCodes->diff($existingObat->keys());

        if ($missing->isNotEmpty()) {
            $errors[] = 'Kode obat tidak ditemukan: '.$missing->implode(', ');
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
        $autoCreateFaskes = $options['auto_create_faskes'] ?? true;
        $dryRun = $options['dry_run'] ?? false;

        $result = [
            'faskes_name' => $data['faskes_name'],
            'dry_run' => $dryRun,
            'targets' => [],
            'errors' => [],
        ];

        $faskesId = $this->resolveFaskesId($data['faskes_name'], $autoCreateFaskes, $dryRun);
        if (! $faskesId) {
            $result['errors'][] = "Faskes '{$data['faskes_name']}' tidak ditemukan dan auto-create nonaktif.";

            return $result;
        }

        $obatMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('id', 'kode_obat');

        $obatVenMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('ven_kategori', 'kode_obat');

        $obatHargaMap = Obat::whereIn('kode_obat', collect($data['obat'])->pluck('kode_obat'))
            ->pluck('harga_satuan', 'kode_obat');

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
            $pemakaianTahunLalu = $obat['total_pemakaian'] ?? 0;
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

    private function importPemakaian(array $data, int $faskesId, $obatMap, int $tahun, bool $dryRun): array
    {
        $reportsCreated = 0;
        $detailsCreated = 0;

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $adaPemakaian = false;
            foreach ($data['obat'] as $obat) {
                if (($obat['bulan'][$bulan]['pemakaian'] ?? 0) > 0) {
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
                    if (($obat['bulan'][$bulan]['pemakaian'] ?? 0) > 0) {
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
                $jml = $obat['bulan'][$bulan]['pemakaian'] ?? 0;
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
}
