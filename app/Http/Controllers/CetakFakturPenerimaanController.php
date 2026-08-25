<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanStok;
use App\Services\PdfSettingsService;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakFakturPenerimaanController extends Controller
{
    public function __invoke(PenerimaanStok $penerimaan)
    {
        abort_if($penerimaan->status === 'draft', 404);

        $penerimaan->load([
            'details.obat',
            'fasilitas',
            'supplier',
            'user',
            'sumberDana',
            'distribusi.fasilitasPengirim',
        ]);

        $faskes = $penerimaan->fasilitas;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::DEFAULT_LAYOUT;

        $filename = 'faktur-penerimaan-'.str_replace('/', '_', $penerimaan->nomor_penerimaan).'.pdf';

        $pdfContent = base64_decode(
            Pdf::view('pdf.faktur-penerimaan', [
                'penerimaan' => $penerimaan,
                'kop' => $kop,
                'layout' => $layout,
                'totalQuantity' => $penerimaan->details->sum('jumlah'),
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
