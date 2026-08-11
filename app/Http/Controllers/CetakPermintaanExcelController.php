<?php

namespace App\Http\Controllers;

use App\Models\PermintaanObat;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CetakPermintaanExcelController extends Controller
{
    public function __invoke()
    {
        $query = PermintaanObat::with(['fasilitasPengirim', 'details'])
            ->withCount('details');

        $user = Auth::user();

        if ($user->hasRole('super_admin') || $user->hasRole('admin_dinas') || $user->hasRole('admin_gudang')) {
            $query->where('tipe_permintaan', 'puskesmas_ke_dinas');
        } else {
            $userFaskesId = $user->fasilitas_kesehatan_id;

            if (blank($userFaskesId)) {
                return response('Unauthorized', 403);
            }

            $userFasilitas = $user->fasilitasKesehatan;

            if ($userFasilitas && $userFasilitas->tipe === 'puskesmas') {
                $pustuIds = $userFasilitas->pustu()->pluck('fasilitas_kesehatan.id');

                $query->where(function ($q) use ($userFaskesId, $pustuIds) {
                    $q->where('fasilitas_pengirim_id', $userFaskesId)
                        ->orWhere(function ($subQ) use ($pustuIds) {
                            $subQ->where('tipe_permintaan', 'pustu_ke_puskesmas')
                                ->whereIn('fasilitas_pengirim_id', $pustuIds);
                        });
                });
            } else {
                $query->where('tipe_permintaan', 'pustu_ke_puskesmas')
                    ->where('fasilitas_pengirim_id', $userFaskesId);
            }
        }

        $records = $query->orderBy('tanggal_permintaan', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permintaan Obat');

        // Title
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'LAPORAN PERMINTAAN OBAT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Dicetak: '.now()->format('d/m/Y H:i'));

        // Headers
        $headers = ['NO', 'NOMOR PERMINTAAN', 'TANGGAL', 'PENGIRIM', 'TIPE', 'JUMLAH ITEM', 'STATUS', 'CATATAN'];
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
        $sheet->getStyle("A{$hRow}:H{$hRow}")->applyFromArray($headerStyle);

        // Data rows
        $row = $hRow + 1;
        $no = 1;

        $statusLabels = [
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'sedang_didistribusi' => 'Sedang Didistribusi',
            'diterima' => 'Diterima',
            'dibatalkan' => 'Dibatalkan',
        ];

        $tipeLabels = [
            'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
            'puskesmas_ke_dinas' => 'Puskesmas → Dinas',
        ];

        foreach ($records as $record) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $record->nomor_permintaan);
            $sheet->setCellValue("C{$row}", $record->tanggal_permintaan?->format('d/m/Y') ?? '-');
            $sheet->setCellValue("D{$row}", $record->fasilitasPengirim?->nama ?? '-');
            $sheet->setCellValue("E{$row}", $tipeLabels[$record->tipe_permintaan] ?? $record->tipe_permintaan);
            $sheet->setCellValue("F{$row}", $record->details_count ?? 0);
            $sheet->setCellValue("G{$row}", $statusLabels[$record->status] ?? $record->status);
            $sheet->setCellValue("H{$row}", $record->catatan ?? '-');

            $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(30);

        $writer = new Xlsx($spreadsheet);
        $filename = 'permintaan-obat-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename);
    }
}
