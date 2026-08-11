<?php

namespace App\Http\Controllers;

use App\Models\LaporanLplpo;
use App\Services\PdfSettingsService;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakLplpoController extends Controller
{
    public function __invoke(LaporanLplpo $lplpo)
    {
        $details = $lplpo->details()->with('obat')->get();
        $faskes = $lplpo->fasilitas;

        $settings = PdfSettingsService::getSettings($faskes?->id);
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();

        $filename = "lplpo-{$lplpo->nomor_laporan}-{$lplpo->periode_tahun}-{$lplpo->periode_bulan}.pdf";

        $pdfContent = base64_decode(
            Pdf::view('pdf.lplpo', [
                'laporan' => $lplpo,
                'details' => $details,
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
}
