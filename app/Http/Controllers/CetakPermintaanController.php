<?php

namespace App\Http\Controllers;

use App\Models\PermintaanObat;
use App\Services\PdfSettingsService;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakPermintaanController extends Controller
{
    public function __invoke(PermintaanObat $permintaan)
    {
        abort_if($permintaan->status === 'draft', 404);

        $permintaan->load([
            'details.obat',
            'fasilitasPengirim',
            'fasilitasTujuan',
            'disetujuiOleh',
            'distribusi',
        ]);

        $faskes = $permintaan->fasilitasPengirim;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();

        $filename = 'permintaan-obat-'.str_replace('/', '_', $permintaan->nomor_permintaan).'.pdf';

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-permintaan', [
                'permintaan' => $permintaan,
                'kop' => $kop,
                'layout' => $layout,
            ])
                ->format('A4')
                ->base64()
        );

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
