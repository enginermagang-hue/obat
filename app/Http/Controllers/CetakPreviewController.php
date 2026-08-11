<?php

namespace App\Http\Controllers;

use App\Services\PdfGenerationService;
use Illuminate\Http\Request;

class CetakPreviewController extends Controller
{
    public function __invoke(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, PdfGenerationService::getValidTypes()), 404);

        $model = PdfGenerationService::getModelClass($type)::findOrFail($id);

        $this->authorizeAccess($type, $model);

        $overrides = $request->only([
            'paper_format', 'orientation',
            'font_family', 'font_size_kop1', 'font_size_kop2',
            'font_size_body',
            'margin_top', 'margin_bottom', 'margin_left', 'margin_right',
        ]);

        $pdfContent = PdfGenerationService::generate($type, $id, $overrides);
        $filename = PdfGenerationService::generateFilename($type, $model);

        $disposition = $request->query('download') ? 'attachment' : 'inline';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    private function authorizeAccess(string $type, $model): void
    {
        match ($type) {
            'faktur-distribusi' => abort_if($model->status === 'draft', 404),
            'faktur-penerimaan' => abort_if($model->status === 'draft', 404),
            'faktur-permintaan' => abort_if($model->status === 'draft', 404),
            'faktur-retur' => abort_if($model->status === 'draft', 404),
            'rko' => abort_if($model->status !== 'disetujui', 403, 'Hanya RKO yang sudah disetujui dapat dicetak.'),
            'neraca' => abort_if($model->status !== 'selesai', 404),
            default => null,
        };
    }
}
