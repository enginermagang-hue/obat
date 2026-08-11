<?php

namespace App\Http\Controllers;

use App\Enums\MetodeStok;
use App\Models\Obat;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CetakObatExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');
        $ven = $request->query('ven');
        $status = $request->query('status');
        $kategori = $request->query('kategori');
        $bentuk = $request->query('bentuk');
        $metode = $request->query('metode');

        $query = Obat::query();

        $query->when($search, fn ($q, $v) => $q->where(function ($q) use ($v) {
            $q->where('kode_obat', 'like', "%{$v}%")
                ->orWhere('nama_obat', 'like', "%{$v}%")
                ->orWhere('nama_generik', 'like', "%{$v}%")
                ->orWhere('kategori', 'like', "%{$v}%");
        }));

        $query->when($ven, fn ($q, $v) => $q->where('ven_kategori', $v));
        $query->when($status, fn ($q, $v) => $q->where('status', $v));
        $query->when($kategori, fn ($q, $v) => $q->where('kategori', $v));
        $query->when($bentuk, fn ($q, $v) => $q->where('bentuk_sediaan', $v));
        $query->when($metode, fn ($q, $v) => $q->where('metode_stok', $v));

        $records = $query->orderBy('kode_obat')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Obat');

        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'DATA OBAT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Dicetak: '.now()->format('d/m/Y H:i'));

        $headers = [
            'NO', 'KODE OBAT', 'NAMA OBAT', 'NAMA GENERIK', 'KATEGORI', 'SATUAN',
            'KEKUATAN', 'BENTUK', 'VEN', 'STATUS', 'METODE STOK', 'HARGA SATUAN',
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
        $sheet->getStyle("A{$hRow}:L{$hRow}")->applyFromArray($headerStyle);

        $venLabels = [
            'V' => 'Vital',
            'E' => 'Esensial',
            'N' => 'Non-Esensial',
        ];

        $bentukLabels = [
            'tablet' => 'Tablet',
            'kapsul' => 'Kapsul',
            'sirup' => 'Sirup',
            'salep' => 'Salep',
            'injeksi' => 'Injeksi',
            'drop' => 'Drop',
            'inhaler' => 'Inhaler',
            'suppositoria' => 'Suppositoria',
        ];

        $row = $hRow + 1;
        $no = 1;

        foreach ($records as $record) {
            $metodeLabel = $record->metode_stok instanceof MetodeStok ? $record->metode_stok->getLabel() : '-';

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $record->kode_obat);
            $sheet->setCellValue("C{$row}", $record->nama_obat);
            $sheet->setCellValue("D{$row}", $record->nama_generik ?? '-');
            $sheet->setCellValue("E{$row}", $record->kategori ?? '-');
            $sheet->setCellValue("F{$row}", $record->satuan ?? '-');
            $sheet->setCellValue("G{$row}", $record->kekuatan ?? '-');
            $sheet->setCellValue("H{$row}", $bentukLabels[$record->bentuk_sediaan] ?? ($record->bentuk_sediaan ?? '-'));
            $sheet->setCellValue("I{$row}", $venLabels[$record->ven_kategori] ?? ($record->ven_kategori ?? '-'));
            $sheet->setCellValue("J{$row}", $record->status === 'aktif' ? 'Aktif' : 'Nonaktif');
            $sheet->setCellValue("K{$row}", $metodeLabel);
            $sheet->setCellValue("L{$row}", $record->harga_satuan ? (float) $record->harga_satuan : 0);

            $sheet->getStyle("A{$row}:L{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(18);

        $sheet->getStyle('L5:L'.($row - 1))->getNumberFormat()
            ->setFormatCode('#,##0');

        $writer = new Xlsx($spreadsheet);
        $filename = 'obat-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename);
    }
}
