<?php

namespace App\Http\Controllers;

use App\Services\PrediksiRekomendasiService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CetakPrediksiExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        $horizon = max(1, (int) $request->query('horizon', 3));

        $rows = (new PrediksiRekomendasiService(
            fasilitasId: $request->query('fasilitas_id') ? (int) $request->query('fasilitas_id') : null,
            bulan: (int) $request->query('bulan', now()->month),
            tahun: (int) $request->query('tahun', now()->year),
            horizon: $horizon,
            kategori: $request->query('kategori') ?: null,
            cari: $request->query('cari') ?: null,
        ))->rows();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prediksi AI');

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'REKOMENDASI PENGADAAN OBAT — PREDIKSI AI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Horizon '.$horizon.' bulan • Dicetak: '.now()->format('d/m/Y H:i'));

        $headers = [
            'NO', 'NAMA OBAT', 'KATEGORI', 'VEN', 'SATUAN', 'STOK SAAT INI',
            'RATA²/BULAN', 'PREDIKSI '.$horizon.' BULAN', 'REKOMENDASI PESAN', 'CONFIDENCE AI', 'STATUS',
        ];
        $hRow = 4;

        foreach ($headers as $colIdx => $header) {
            $col = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue($col.$hRow, $header);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A{$hRow}:{$lastColumn}{$hRow}")->applyFromArray($headerStyle);

        $rowIdx = $hRow + 1;
        $no = 1;

        foreach ($rows as $record) {
            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", $record['nama_obat']);
            $sheet->setCellValue("C{$rowIdx}", $record['kategori'] ?? '-');
            $sheet->setCellValue("D{$rowIdx}", $record['ven_kategori'] ?? '-');
            $sheet->setCellValue("E{$rowIdx}", $record['satuan'] ?? '-');
            $sheet->setCellValue("F{$rowIdx}", $record['stok']);
            $sheet->setCellValue("G{$rowIdx}", $record['rata_per_bulan']);
            $sheet->setCellValue("H{$rowIdx}", $record['prediksi_horizon']);
            $sheet->setCellValue("I{$rowIdx}", $record['rekom']);
            $sheet->setCellValue("J{$rowIdx}", $record['akurasi'] !== null ? round($record['akurasi'] * 100, 1).'%' : '-');
            $sheet->setCellValue("K{$rowIdx}", $record['status']);

            $sheet->getStyle("A{$rowIdx}:K{$rowIdx}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $rowIdx++;
        }

        $widths = ['A' => 5, 'B' => 38, 'C' => 20, 'D' => 6, 'E' => 12, 'F' => 14, 'G' => 14, 'H' => 16, 'I' => 18, 'J' => 15, 'K' => 15];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->getStyle('F5:K'.($rowIdx - 1))->getNumberFormat()->setFormatCode('#,##0');

        $writer = new Xlsx($spreadsheet);
        $filename = 'prediksi-ai-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename);
    }
}
