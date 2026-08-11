<?php

namespace App\Helpers;

use App\Models\BatchStok;
use App\Models\Obat;
use Carbon\Carbon;

class BatchNumberGenerator
{
    /**
     * Generate nomor batch obat unik.
     *
     * Format: {KodeObat}-{YYMM}-{XXXX}
     * Contoh: PARA-2606-0001
     *
     * @param  int  $obatId  ID obat
     * @param  string|null  $date  Tanggal referensi (Y-m-d), default hari ini
     */
    public static function generate(int $obatId, ?string $date = null): string
    {
        if (! config('app.batch_number_auto_generate')) {
            return '';
        }

        if ($obatId <= 0) {
            return '';
        }

        $obat = Obat::findOrFail($obatId);

        $refDate = $date ? Carbon::parse($date) : now();
        $yy = $refDate->format('y');
        $mm = $refDate->format('m');

        $prefix = strtoupper($obat->kode_obat).'-'.$yy.$mm.'-';

        $lastBatch = BatchStok::query()
            ->where('obat_id', $obatId)
            ->where('batch_number', 'like', $prefix.'%')
            ->orderByDesc('batch_number')
            ->value('batch_number');

        if ($lastBatch) {
            $lastSeq = (int) substr($lastBatch, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix.str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
