<?php

namespace App\Http\Controllers;

use App\Models\PermintaanObat;
use Illuminate\Support\Facades\Storage;

class DownloadSuratPermintaanController extends Controller
{
    public function __invoke(PermintaanObat $permintaan)
    {
        abort_if(blank($permintaan->surat_permintaan), 404);
        abort_if($permintaan->status !== 'menunggu_persetujuan', 404);

        $user = auth()->user();

        if (! $user->hasRole(['super_admin', 'admin_dinas', 'puskesmas'])) {
            abort(403);
        }

        $path = $permintaan->surat_permintaan;

        abort_unless(Storage::disk('public')->exists($path), 404);

        $filename = 'surat-permintaan-'.str_replace('/', '_', $permintaan->nomor_permintaan).'.pdf';

        return Storage::disk('public')->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
