<?php

namespace App\Http\Controllers;

use App\Models\LaporanRko;
use App\Services\PdfSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use League\Csv\Writer;
use Spatie\LaravelPdf\Facades\Pdf;

class CetakRkoController extends Controller
{
    use AuthorizesRequests;

    public function cetakPdf(LaporanRko $rko)
    {
        $this->authorize('view', $rko);
        abort_if($rko->status !== 'disetujui', 403, 'Hanya RKO yang sudah disetujui dapat dicetak.');

        $rko->load([
            'details.obat',
            'fasilitas',
            'dibuatOleh',
            'disetujuiOleh',
        ]);

        $faskes = $rko->fasilitas;
        $kop = PdfSettingsService::getKopSurat($faskes?->id);
        $layout = PdfSettingsService::DEFAULT_LAYOUT;

        $filename = "rko-{$rko->nomor_rko}-{$rko->periode_tahun}.pdf";

        $pdfContent = base64_decode(
            Pdf::view('pdf.rko', [
                'laporan' => $rko,
                'details' => $rko->details,
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

    public function cetakXls(LaporanRko $rko)
    {
        $this->authorize('view', $rko);
        abort_if($rko->status !== 'disetujui', 403, 'Hanya RKO yang sudah disetujui dapat diekspor.');

        $rko->load(['details.obat', 'fasilitas']);

        $details = $rko->details()->with('obat')->get();

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'No', 'Kode', 'Nama Obat', 'Satuan', 'ABC', 'VEN',
            'Pakai Th Lalu', 'Rata²/Bln', 'Sisa Stok', 'Keb. 18 Bln',
            'Rencana Keb.', 'Usulan', 'Buffer %', 'Buffer Qty',
            'Total Keb.', 'Harga Perkiraan', 'Total Harga', 'Keterangan',
        ]);

        $totalAnggaran = 0.0;

        foreach ($details as $i => $detail) {
            $csv->insertOne([
                $i + 1,
                $detail->obat?->kode_obat ?? '-',
                $detail->obat?->nama_obat ?? '-',
                $detail->obat?->satuan ?? '-',
                $detail->abc_kategori ?? '-',
                $detail->ven_kategori ?? '-',
                (int) ($detail->pemakaian_tahun_sebelumnya ?? 0),
                (int) ($detail->rata_rata_pemakaian_bulanan ?? 0),
                (int) ($detail->stok_akhir ?? 0),
                (int) ($detail->kebutuhan_tahunan ?? 0),
                (int) ($detail->rencana_kebutuhan ?? 0),
                (int) ($detail->usulan ?? 0),
                (float) ($detail->buffer_stock_persen ?? 0),
                (int) ($detail->buffer_stok_qty ?? 0),
                (int) ($detail->total_kebutuhan ?? 0),
                (float) ($detail->harga_perkiraan ?? 0),
                (float) ($detail->total_harga ?? 0),
                $detail->keterangan ?? '',
            ]);

            $totalAnggaran += (float) ($detail->total_harga ?? 0);
        }

        if ($details->isNotEmpty()) {
            $csv->insertOne([
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                'Total Anggaran', '', round($totalAnggaran, 2), '',
            ]);
        }

        $filename = "rko-{$rko->nomor_rko}-{$rko->periode_tahun}.csv";

        return response($csv->getContent(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
