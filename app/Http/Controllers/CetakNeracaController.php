<?php

namespace App\Http\Controllers;

use App\Models\NeracaTahunan;
use App\Models\SumberDana;
use App\Services\PdfSettingsService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakNeracaController extends Controller
{
    public function cetakPdf(NeracaTahunan $neraca)
    {
        abort_if($neraca->status !== 'selesai', 404);

        $details = $neraca->details()->with(['obat', 'sumberDanaDetails.sumberDana'])->get();
        $faskes = $neraca->fasilitas;

        $sumberDanaList = SumberDana::whereIn('id', function ($q) use ($neraca) {
            $q->select('sumber_dana_id')
                ->from('detail_neraca_sumber_dana')
                ->join('detail_neraca_tahunan', 'detail_neraca_sumber_dana.detail_neraca_id', '=', 'detail_neraca_tahunan.id')
                ->where('detail_neraca_tahunan.neraca_id', $neraca->id)
                ->distinct();
        })->where('tahun', $neraca->tahun)->orderBy('kode')->get()->values();

        $settings = PdfSettingsService::getSettings($faskes?->id);
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();

        $filename = "neraca-tahunan-{$neraca->nomor_neraca}-{$neraca->tahun}.pdf";

        $pdfContent = base64_decode(
            Pdf::view('pdf.neraca-tahunan', [
                'neraca' => $neraca,
                'details' => $details,
                'sumberDanaList' => $sumberDanaList,
                'kop' => $kop,
                'settings' => $settings,
                'layout' => $layout,
            ])
                ->format('A4')
                ->landscape()
                ->base64()
        );

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function cetakXls(NeracaTahunan $neraca)
    {
        abort_if($neraca->status !== 'selesai', 403, 'Hanya Neraca yang sudah selesai dapat diekspor.');

        $neraca->load(['details.obat', 'details.sumberDanaDetails.sumberDana', 'fasilitas', 'dibuatOleh']);

        $sumberDanaList = SumberDana::whereIn('id', function ($q) use ($neraca) {
            $q->select('sumber_dana_id')
                ->from('detail_neraca_sumber_dana')
                ->join('detail_neraca_tahunan', 'detail_neraca_sumber_dana.detail_neraca_id', '=', 'detail_neraca_tahunan.id')
                ->where('detail_neraca_tahunan.neraca_id', $neraca->id)
                ->distinct();
        })->where('tahun', $neraca->tahun)->orderBy('kode')->get()->values();

        if ($sumberDanaList->isEmpty()) {
            $sumberDanaList = collect([(object) ['id' => null, 'kode' => 'TOTAL']]);
        }

        $sdCount = $sumberDanaList->count();

        // Column layout: NO(1) + NAMA(1) + SATUAN(1) + STOK_AWAL(sd) + MASUK(sd) + KELUAR(sd) + AKHIR(sd) + HARGA(1) + SALDO(sd) + JML_PERS(1)
        $stokAwalStart = 4;
        $masukStart = $stokAwalStart + $sdCount;
        $keluarStart = $masukStart + $sdCount;
        $akhirStart = $keluarStart + $sdCount;
        $akhirEnd = $akhirStart + $sdCount - 1;
        $hargaCol = $akhirEnd + 1;
        $saldoStart = $hargaCol + 1;
        $saldoEnd = $saldoStart + $sdCount - 1;
        $totalPersediaanCol = $saldoEnd + 1;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Neraca Tahunan');

        $faskesName = $neraca->fasilitas?->nama ?? 'Gudang Dinas Kesehatan';
        $lastCol = $this->getColumnLetter($totalPersediaanCol);

        // === TITLE ROWS ===
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'NERACA OBAT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', strtoupper($faskesName));
        $sheet->getStyle('A2')->getFont()->setSize(12);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', "TAHUN {$neraca->tahun}");
        $sheet->getStyle('A3')->getFont()->setSize(12);

        // === HEADER ROW 1: Group headers ===
        $hRow1 = 4;
        $sheet->setCellValue('A'.$hRow1, 'NO');
        $sheet->setCellValue('B'.$hRow1, 'NAMA OBAT/BMHP');
        $sheet->setCellValue('C'.$hRow1, 'SATUAN');
        $sheet->setCellValue($this->getColumnLetter($stokAwalStart).$hRow1, "PERSEDIAAN {$neraca->tahun}");
        $sheet->mergeCells($this->getColumnLetter($stokAwalStart).$hRow1.':'.$this->getColumnLetter($totalPersediaanCol).$hRow1);

        $sheet->mergeCells('A'.$hRow1.':A'.($hRow1 + 1));
        $sheet->mergeCells('B'.$hRow1.':B'.($hRow1 + 1));
        $sheet->mergeCells('C'.$hRow1.':C'.($hRow1 + 1));

        // === HEADER ROW 2: Sub-headers ===
        $hRow2 = $hRow1 + 1;

        $sheet->setCellValue($this->getColumnLetter($stokAwalStart).$hRow2, 'STOK AWAL PERSEDIAAN');
        $sheet->mergeCells($this->getColumnLetter($stokAwalStart).$hRow2.':'.$this->getColumnLetter($masukStart - 1).$hRow2);

        $sheet->setCellValue($this->getColumnLetter($masukStart).$hRow2, 'MUTASI MASUK');
        $sheet->mergeCells($this->getColumnLetter($masukStart).$hRow2.':'.$this->getColumnLetter($keluarStart - 1).$hRow2);

        $sheet->setCellValue($this->getColumnLetter($keluarStart).$hRow2, "MUTASI KELUAR {$neraca->tahun}");
        $sheet->mergeCells($this->getColumnLetter($keluarStart).$hRow2.':'.$this->getColumnLetter($akhirStart - 1).$hRow2);

        $sheet->setCellValue($this->getColumnLetter($akhirStart).$hRow2, 'STOK AKHIR PERSEDIAAN');
        $sheet->mergeCells($this->getColumnLetter($akhirStart).$hRow2.':'.$this->getColumnLetter($akhirEnd).$hRow2);

        $sheet->setCellValue($this->getColumnLetter($hargaCol).$hRow2, 'HARGA SATUAN');
        $sheet->mergeCells($this->getColumnLetter($hargaCol).$hRow2.':'.$this->getColumnLetter($hargaCol).($hRow2 + 1));

        $sheet->setCellValue($this->getColumnLetter($saldoStart).$hRow2, 'SALDO PERSEDIAAN (Rp)');
        $sheet->mergeCells($this->getColumnLetter($saldoStart).$hRow2.':'.$this->getColumnLetter($saldoEnd).$hRow2);

        $sheet->setCellValue($this->getColumnLetter($totalPersediaanCol).$hRow2, 'JUMLAH PERSEDIAAN (Rp)');
        $sheet->mergeCells($this->getColumnLetter($totalPersediaanCol).$hRow2.':'.$this->getColumnLetter($totalPersediaanCol).($hRow2 + 1));

        // === HEADER ROW 3: SD codes + Row numbers ===
        $hRow3 = $hRow2 + 1;

        $colIdx = $stokAwalStart;
        foreach (range(1, 3) as $_) {
            foreach ($sumberDanaList as $sd) {
                $sheet->setCellValue($this->getColumnLetter($colIdx).$hRow3, $sd->kode);
                $colIdx++;
            }
        }
        // Stok Akhir SD codes
        foreach ($sumberDanaList as $sd) {
            $sheet->setCellValue($this->getColumnLetter($colIdx).$hRow3, $sd->kode);
            $colIdx++;
        }
        // Saldo SD codes
        $colIdx = $saldoStart;
        foreach ($sumberDanaList as $sd) {
            $sheet->setCellValue($this->getColumnLetter($colIdx).$hRow3, $sd->kode);
            $colIdx++;
        }

        // Row number row
        $hRow4 = $hRow3 + 1;
        $colNum = 1;
        $sheet->setCellValue('A'.$hRow4, $colNum++);
        $sheet->setCellValue('B'.$hRow4, $colNum++);
        $sheet->setCellValue('C'.$hRow4, $colNum++);
        for ($ci = $stokAwalStart; $ci <= $akhirEnd; $ci++) {
            $sheet->setCellValue($this->getColumnLetter($ci).$hRow4, $colNum++);
        }
        $sheet->setCellValue($this->getColumnLetter($hargaCol).$hRow4, $colNum++);
        for ($ci = $saldoStart; $ci <= $saldoEnd; $ci++) {
            $sheet->setCellValue($this->getColumnLetter($ci).$hRow4, $colNum++);
        }
        $sheet->setCellValue($this->getColumnLetter($totalPersediaanCol).$hRow4, $colNum++);

        // Header styles
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle("A{$hRow1}:{$lastCol}{$hRow4}")->applyFromArray($headerStyle);

        // === DATA ROWS (grouped by kategori) ===
        $dataStartRow = $hRow4 + 1;
        $row = $dataStartRow;
        $no = 1;

        $grouped = $neraca->details->groupBy(fn ($d) => $d->obat?->kategori ?? 'LAINNYA');
        $grouped = $grouped->sortKeys();

        foreach ($grouped as $kategori => $items) {
            // Category header row (yellow)
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("A{$row}", strtoupper($kategori));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $row++;

            foreach ($items as $detail) {
                $sheet->setCellValue("A{$row}", $no++);
                $sheet->setCellValue("B{$row}", $detail->obat?->nama_obat ?? '-');
                $sheet->setCellValue("C{$row}", $detail->obat?->satuan ?? '-');

                $sdDetails = $detail->sumberDanaDetails->keyBy('sumber_dana_id');
                $hargaSatuan = $detail->harga_satuan ?? 0;

                // Harga Satuan (number only)
                $sheet->setCellValue($this->getColumnLetter($hargaCol).$row, $hargaSatuan);
                $sheet->getStyle($this->getColumnLetter($hargaCol).$row)->getNumberFormat()
                    ->setFormatCode('#,##0');

                $hargaCellRef = $this->getColumnLetter($hargaCol).$row;

                // Stok Awal (quantity per SD, fallback to aggregate)
                $colIdx = $stokAwalStart;
                foreach ($sumberDanaList as $sd) {
                    $sdData = $sdDetails->get($sd->id);
                    $val = $sdData ? ($sdData->stok_awal_jumlah ?? 0) : ($detail->stok_awal ?? 0);
                    $sheet->setCellValue($this->getColumnLetter($colIdx).$row, $val);
                    $colIdx++;
                }

                // Mutasi Masuk (quantity per SD, fallback to aggregate)
                foreach ($sumberDanaList as $sd) {
                    $sdData = $sdDetails->get($sd->id);
                    $val = $sdData ? ($sdData->masuk_jumlah ?? 0) : ($detail->total_masuk ?? 0);
                    $sheet->setCellValue($this->getColumnLetter($colIdx).$row, $val);
                    $colIdx++;
                }

                // Mutasi Keluar (quantity per SD, fallback to aggregate)
                foreach ($sumberDanaList as $sd) {
                    $sdData = $sdDetails->get($sd->id);
                    $val = $sdData ? ($sdData->keluar_jumlah ?? 0) : ($detail->total_keluar ?? 0);
                    $sheet->setCellValue($this->getColumnLetter($colIdx).$row, $val);
                    $colIdx++;
                }

                // Stok Akhir (formula: awal + masuk - keluar per SD)
                $colIdx = $akhirStart;
                foreach ($sumberDanaList as $sdIdx => $sd) {
                    $awalCol = $this->getColumnLetter($stokAwalStart + $sdIdx);
                    $masukCol = $this->getColumnLetter($masukStart + $sdIdx);
                    $keluarCol = $this->getColumnLetter($keluarStart + $sdIdx);
                    $akhirCol = $this->getColumnLetter($colIdx);
                    $sheet->setCellValue($akhirCol.$row, "={$awalCol}{$row}+{$masukCol}{$row}-{$keluarCol}{$row}");
                    $colIdx++;
                }

                // Saldo Persediaan (formula: stok_akhir × harga per SD)
                $saldoColIdx = $saldoStart;
                $saldoCells = [];
                foreach ($sumberDanaList as $sdIdx => $sd) {
                    $akhirCol = $this->getColumnLetter($akhirStart + $sdIdx);
                    $saldoCol = $this->getColumnLetter($saldoColIdx);
                    $sheet->setCellValue($saldoCol.$row, "={$akhirCol}{$row}*{$hargaCellRef}");
                    $sheet->getStyle($saldoCol.$row)->getNumberFormat()->setFormatCode('#,##0');
                    $saldoCells[] = $saldoCol.$row;
                    $saldoColIdx++;
                }

                // Jumlah Persediaan (sum of saldo)
                $saldoRange = implode('+', $saldoCells);
                $sheet->setCellValue($this->getColumnLetter($totalPersediaanCol).$row, "={$saldoRange}");
                $sheet->getStyle($this->getColumnLetter($totalPersediaanCol).$row)->getNumberFormat()
                    ->setFormatCode('#,##0');

                $row++;
            }
        }

        // === FOOTER: 2 total rows (green) ===
        $row++; // blank row

        // Total row 1: Saldo + Jumlah
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:C{$row}");

        for ($ci = $saldoStart; $ci <= $totalPersediaanCol; $ci++) {
            $colLetter = $this->getColumnLetter($ci);
            $firstDataCol = $colLetter.$dataStartRow;
            $lastDataCol = $colLetter.($row - 2);
            $sheet->setCellValue($colLetter.$row, "=SUM({$firstDataCol}:{$lastDataCol})");
            $sheet->getStyle($colLetter.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        $greenStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($greenStyle);

        $row++; // blank row

        // Total row 2: Grand total (Saldo + Jumlah only)
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:C{$row}");

        for ($ci = $saldoStart; $ci <= $totalPersediaanCol; $ci++) {
            $colLetter = $this->getColumnLetter($ci);
            $sheet->setCellValue($colLetter.$row, "=SUM({$colLetter}".($row - 2).':'.$colLetter.($row - 1).')');
            $sheet->getStyle($colLetter.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($greenStyle);

        // === FINAL STYLES ===
        $sheet->getStyle("A{$hRow1}:{$lastCol}{$row}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(10);
        for ($ci = $stokAwalStart; $ci <= $totalPersediaanCol; $ci++) {
            $sheet->getColumnDimension($this->getColumnLetter($ci))->setWidth(14);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "neraca-tahunan-{$neraca->nomor_neraca}-{$neraca->tahun}.xlsx";

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename);
    }

    private function getColumnLetter(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }
}
