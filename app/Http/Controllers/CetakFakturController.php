<?php

namespace App\Http\Controllers;

use App\Models\DistribusiObat;
use App\Services\PdfSettingsService;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakFakturController extends Controller
{
    public function __invoke(DistribusiObat $distribusi)
    {
        abort_if($distribusi->status === 'draft', 404);

        $distribusi->load([
            'fasilitasPengirim',
            'fasilitasPenerima',
            'details.obat',
            'details.batch',
            'pengirim',
        ]);

        $faskes = $distribusi->fasilitasPengirim;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::getLayout();

        $filename = "faktur-distribusi-{$distribusi->nomor_surat_jalan}.pdf";

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-distribusi', [
                'distribusi' => $distribusi,
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
