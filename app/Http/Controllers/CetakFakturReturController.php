<?php

namespace App\Http\Controllers;

use App\Models\ReturObat;
use App\Services\PdfSettingsService;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakFakturReturController extends Controller
{
    public function __invoke(ReturObat $retur)
    {
        abort_if($retur->status === 'draft', 404);

        $retur->load([
            'details.obat',
            'details.batch',
            'fasilitasPengirim',
            'fasilitasPenerima',
            'supplier',
            'disetujuiOleh',
        ]);

        $faskes = $retur->fasilitasPengirim;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::DEFAULT_LAYOUT;

        $filename = 'faktur-retur-'.str_replace('/', '_', $retur->nomor_retur).'.pdf';

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-retur', [
                'retur' => $retur,
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
