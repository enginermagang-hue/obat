<?php

namespace App\Http\Controllers;

use App\Models\LaporanRko;
use App\Services\PdfSettingsService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakRkoController extends Controller
{
    public function cetakPdf(LaporanRko $rko)
    {
        if ($rko->status !== 'disetujui') {
            abort(403, 'Hanya RKO yang sudah disetujui dapat dicetak.');
        }

        $rko->load(['details.obat', 'fasilitas', 'dibuatOleh', 'disetujuiOleh']);
        $faskes = $rko->fasilitas;

        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();

        $filename = "rko-{$rko->nomor_rko}-{$rko->periode_tahun}.pdf";

        $pdfContent = base64_decode(
            Pdf::view('pdf.rko', [
                'laporan' => $rko,
                'details' => $rko->details,
                'kop' => $kop,
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

    public function cetakXls(LaporanRko $rko)
    {
        if ($rko->status !== 'disetujui') {
            abort(403, 'Hanya RKO yang sudah disetujui dapat diekspor.');
        }

        $rko->load(['details.obat', 'fasilitas', 'dibuatOleh', 'disetujuiOleh']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('RKO '.$rko->periode_tahun);

        $boldStyle = ['font' => ['bold' => true]];
        $headerFill = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];
        $cellBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];
        $numberFormat = [NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1];
        $currencyFormat = ['numberFormat' => ['formatCode' => '#,##0']];

        $row = 1;
        $infoRows = [
            ['Nomor RKO', $rko->nomor_rko],
            ['Fasilitas Kesehatan', $rko->fasilitas?->nama ?? '-'],
            ['Periode Tahun', $rko->periode_tahun],
            ['Total Anggaran', $rko->total_anggaran],
            ['Tanggal Pengajuan', $rko->tanggal_pengajuan?->format('d/m/Y') ?? '-'],
            ['Tanggal Disetujui', $rko->tanggal_disetujui?->format('d/m/Y') ?? '-'],
            ['Dibuat Oleh', $rko->dibuatOleh?->name ?? '-'],
            ['Disetujui Oleh', $rko->disetujuiOleh?->name ?? '-'],
        ];

        foreach ($infoRows as $infoRow) {
            $sheet->setCellValue("A{$row}", $infoRow[0])->getStyle("A{$row}")->applyFromArray($boldStyle);
            $sheet->setCellValue("B{$row}", $infoRow[1]);
            if ($infoRow[0] === 'Total Anggaran' && is_numeric($infoRow[1])) {
                $sheet->getStyle("B{$row}")->applyFromArray($currencyFormat);
            }
            $row++;
        }

        $row += 1;

        $headers = [
            'No', 'Kode', 'Nama Obat', 'Satuan', 'ABC', 'VEN',
            'Pemakaian Th Lalu', 'Rata-rata/Bln', 'Sisa Stok',
            'Kebutuhan 18 Bln', 'Rencana Kebutuhan', 'Usulan',
            'Buffer (%)', 'Buffer Qty', 'Total Kebutuhan',
            'Harga Perkiraan', 'Total Harga', 'Keterangan',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $col++;
        }
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($headerFill);

        $row++;
        $dataStartRow = $row;

        foreach ($rko->details as $index => $detail) {
            $col = 'A';
            $values = [
                $index + 1,
                $detail->obat?->kode_obat ?? '-',
                $detail->obat?->nama_obat ?? '-',
                $detail->obat?->satuan ?? '-',
                $detail->abc_kategori ?? '-',
                $detail->ven_kategori ?? '-',
                $detail->pemakaian_tahun_sebelumnya,
                $detail->rata_rata_pemakaian_bulanan,
                $detail->stok_akhir,
                $detail->kebutuhan_tahunan,
                $detail->rencana_kebutuhan,
                $detail->usulan,
                $detail->buffer_stock_persen,
                $detail->buffer_stok_qty,
                $detail->total_kebutuhan,
                $detail->harga_perkiraan,
                $detail->total_harga,
                $detail->keterangan ?? '-',
            ];

            foreach ($values as $value) {
                $sheet->setCellValue("{$col}{$row}", $value);
                $col++;
            }

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($cellBorder);

            $numberCols = ['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
            foreach ($numberCols as $nc) {
                $cellRef = "{$nc}{$row}";
                if (is_numeric($sheet->getCell($cellRef)->getValue())) {
                    $sheet->getStyle($cellRef)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
            }

            $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("Q{$row}")->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue("A{$totalRow}", 'TOTAL')->getStyle("A{$totalRow}")->applyFromArray($boldStyle);
        $sheet->setCellValue("Q{$totalRow}", "=SUM(Q{$dataStartRow}:Q".($totalRow - 1).')');
        $sheet->getStyle("Q{$totalRow}")->applyFromArray(array_merge($boldStyle, $currencyFormat));
        $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '374151']]],
        ]);

        foreach (range('A', $lastCol) as $columnId) {
            $sheet->getColumnDimension($columnId)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        $filename = "rko-{$rko->nomor_rko}-{$rko->periode_tahun}.xlsx";

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
