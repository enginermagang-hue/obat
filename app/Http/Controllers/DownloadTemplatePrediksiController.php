<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadTemplatePrediksiController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse|StreamedResponse
    {
        $tahun = (int) $request->query('tahun', 2024);
        if ($tahun < 2022 || $tahun > 2035) {
            $tahun = 2024;
        }

        // Selalu generate dinamis agar header sesuai tahun ?tahun= (hindari cache stale)
        // File statis diabaikan — dihapus via artisan jika ada

        $spreadsheet = $this->buildSpreadsheet($tahun);
        $writer = new Xlsx($spreadsheet);
        $filename = 'template-prediksi-wide.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildSpreadsheet(int $tahun = 2024): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        $periodeHeaders = array_map(fn ($m) => sprintf('%04d-%02d', $tahun, $m), range(1, 12));
        $headers = array_merge(['kode_obat', 'nama_obat', 'satuan', 'harga'], $periodeHeaders, ['stok_akhir', 'keterangan']);
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $col++;
        }

        $data = [
            ['92000146', 'Albendazole 400 mg Tablet', 'Tablet', 457, 0, 0, 10, 0, 2410, 580, 0, 0, 0, 0, 0, 0, 3050, ''],
            ['92000281', 'Alopurinol 100 mg Tablet', 'Tablet', 99, 100, 0, 100, 0, 0, 100, 0, 0, 10, 40, 20, 30, 120, ''],
            ['92000300', 'Amoxicillin 500 mg Kapsul', 'Kapsul', 250, 120, 110, 130, 150, 140, 160, 155, 145, 135, 125, 130, 140, 500, 'contoh'],
        ];
        $row = 2;
        foreach ($data as $r) {
            $col = 'A';
            foreach ($r as $v) {
                $sheet->setCellValue($col.$row, $v);
                $col++;
            }
            $row++;
        }

        $lastCol = $sheet->getHighestColumn();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
        ];
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastCol.'1');

        $widths = ['A' => 14, 'B' => 32, 'C' => 10, 'D' => 10, 'E' => 10, 'F' => 10, 'G' => 10, 'H' => 10, 'I' => 10, 'J' => 10, 'K' => 10, 'L' => 10, 'M' => 10, 'N' => 10, 'O' => 10, 'P' => 10, 'Q' => 12, 'R' => 20];
        foreach ($widths as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $sheet->getStyle('D2:Q100')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A2:A100')->getNumberFormat()->setFormatCode('@');

        $info = $spreadsheet->createSheet();
        $info->setTitle('Petunjuk');
        $info->setCellValue('A1', 'Petunjuk Template Prediksi Wide (Opsi A)');
        $info->setCellValue('A2', 'Format minimal untuk Prediksi Kebutuhan (AI Gradient Boost / Moving Average)');
        $info->setCellValue('A4', 'Kolom');
        $info->setCellValue('B4', 'Wajib');
        $info->setCellValue('C4', 'Keterangan');
        $rows = [
            ['kode_obat', 'Ya', 'Kode obat harus ada di master obat (contoh: 92000146)'],
            ['nama_obat', 'Tidak', 'Hanya info, tidak divalidasi ketat'],
            ['satuan', 'Tidak', 'Tablet / Kapsul / Botol'],
            ['harga', 'Tidak', 'Harga satuan, opsional'],
            [sprintf('%04d-01 .. %04d-12', $tahun, $tahun), 'Ya (min 6)', sprintf('Pemakaian bulanan per obat. Header harus YYYY-MM, contoh %04d-01', $tahun)],
            ['stok_akhir', 'Tidak', 'Sisa stok akhir Desember, untuk fitur stok_saat_ini'],
            ['keterangan', 'Tidak', 'Catatan bebas'],
        ];
        $r = 5;
        foreach ($rows as $rr) {
            $info->setCellValue('A'.$r, $rr[0]);
            $info->setCellValue('B'.$r, $rr[1]);
            $info->setCellValue('C'.$r, $rr[2]);
            $r++;
        }
        $info->setCellValue('A13', 'Catatan:');
        $info->setCellValue('A14', '- 1 file = 1 faskes × 1 tahun. Pilih faskes & tahun di halaman Import sebelum upload.');
        $info->setCellValue('A15', sprintf('- Header periode YYYY-MM harus sesuai Tahun terpilih (misal %04d-01..%04d-12 jika pilih %04d).', $tahun, $tahun, $tahun));
        $info->setCellValue('A16', '- Minimal 6 bulan untuk AI, 3 bulan untuk fallback Moving Average. 12 bulan optimal.');
        $info->setCellValue('A17', '- Jangan isi nama/kode faskes di sheet. Faskes dipilih via dropdown.');
        $info->getColumnDimension('A')->setWidth(22);
        $info->getColumnDimension('B')->setWidth(12);
        $info->getColumnDimension('C')->setWidth(70);
        $info->getStyle('A4:C4')->getFont()->setBold(true);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
