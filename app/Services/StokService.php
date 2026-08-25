<?php

namespace App\Services;

use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPemakaianObat;
use App\Models\DetailPenerimaanStok;
use App\Models\DetailReturObat;
use App\Models\DistribusiObat;
use App\Models\OpnameStok;
use App\Models\PemakaianObat;
use App\Models\PenerimaanStok;
use App\Models\ReturObat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\SumberDanaPenggunaan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StokService
{
    /**
     * Proses penerimaan stok: update batch, update stok agregat, catat riwayat.
     */
    public function prosesPenerimaan(PenerimaanStok $penerimaan): void
    {
        foreach ($penerimaan->details as $detail) {
            $this->prosesBatchDariPenerimaan($detail, $penerimaan);

            $stok = $this->getStokTarget($penerimaan->fasilitas_id, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->increment('jumlah', $detail->jumlah);

            $this->catatRiwayat(
                fasilitasId: $penerimaan->fasilitas_id,
                obatId: $detail->obat_id,
                tipe: 'masuk',
                jumlah: $detail->jumlah,
                stokSebelum: $stokSebelum,
                referensi: $penerimaan,
                userId: $penerimaan->user_id,
                keterangan: 'Penerimaan: '.$penerimaan->nomor_penerimaan.' ('.$penerimaan->tipe.')',
                tanggal: $penerimaan->tanggal_penerimaan,
            );

            // Safety net: sync aggregate from batch_stok
            if ($penerimaan->fasilitas_id) {
                BatchStok::recalculateFaskes($penerimaan->fasilitas_id, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }

        // Catat realisasi anggaran jika penerimaan memiliki sumber dana
        if ($penerimaan->sumber_dana_id) {
            SumberDanaPenggunaan::create([
                'sumber_dana_id' => $penerimaan->sumber_dana_id,
                'rko_id' => null,
                'fasilitas_id' => $penerimaan->fasilitas_id,
                'tipe' => 'realisasi',
                'jumlah_obat' => $penerimaan->details->sum('jumlah'),
                'total_biaya' => $penerimaan->total_biaya ?? 0,
                'tanggal' => $penerimaan->tanggal_penerimaan,
                'keterangan' => 'Penerimaan: '.$penerimaan->nomor_penerimaan,
            ]);
        }
    }

    /**
     * Proses penerimaan distribusi: konfirmasi PenerimaanStok bertipe 'distribusi' yang
     * menaut ke sebuah DistribusiObat. Mutasi stok terjadi dua arah: tambah stok faskes
     * penerima, kurangi stok pengirim (faskes atau gudang), dan update status distribusi
     * + permintaan terkait.
     *
     * @throws \InvalidArgumentException Jika tipe bukan 'distribusi' atau tidak ada distribusi.
     * @throws \RuntimeException Jika distribusi tidak dalam status yang valid atau stok tidak cukup.
     */
    public function prosesPenerimaanDistribusi(PenerimaanStok $penerimaan): void
    {
        if ($penerimaan->tipe !== 'distribusi') {
            throw new \InvalidArgumentException(
                'prosesPenerimaanDistribusi hanya untuk PenerimaanStok bertipe distribusi. Tipe saat ini: '.$penerimaan->tipe,
            );
        }

        $distribusi = $penerimaan->distribusi;
        if (! $distribusi) {
            throw new \RuntimeException(
                'PenerimaanStok '.$penerimaan->nomor_penerimaan.' tidak memiliki DistribusiObat terkait.',
            );
        }

        if ($distribusi->status !== 'dalam_pengiriman') {
            throw new \RuntimeException(
                "Distribusi {$distribusi->nomor_surat_jalan} berstatus '{$distribusi->status}', harus 'dalam_pengiriman' untuk dikonfirmasi.",
            );
        }

        if ($penerimaan->fasilitas_id !== $distribusi->fasilitas_penerima_id) {
            throw new \RuntimeException(
                'Fasilitas penerima (PenerimaanStok.fasilitas_id) tidak sesuai dengan DistribusiObat.fasilitas_penerima_id.',
            );
        }

        $userId = $penerimaan->user_id;

        DB::transaction(function () use ($penerimaan, $distribusi, $userId): void {
            foreach ($penerimaan->details as $detail) {
                // Cari distribusi detail yang cocok (obat_id + batch_number + tanggal_expired)
                $distDetail = $distribusi->details()
                    ->where('obat_id', $detail->obat_id)
                    ->whereHas('batch', function ($q) use ($detail): void {
                        $q->where('batch_number', $detail->batch_number)
                            ->whereDate('tanggal_expired', $detail->tanggal_expired);
                    })
                    ->first();

                if (! $distDetail) {
                    throw new \RuntimeException(
                        "Item obat ID {$detail->obat_id} batch {$detail->batch_number} (expired {$detail->tanggal_expired?->format('Y-m-d')}) ".
                        "tidak ditemukan di Distribusi {$distribusi->nomor_surat_jalan}.",
                    );
                }

                // 1. Tambah stok penerima (reuse helper dari DistribusiObat flow)
                $this->tambahStokPenerimaDistribusi($distribusi, $distDetail, $userId);

                // 2. Kurangi stok pengirim (reuse helper, dengan validasi stok cukup)
                $sourceBatch = $distDetail->batch;
                if ($sourceBatch && $sourceBatch->jumlah < $detail->jumlah) {
                    throw new \RuntimeException(
                        "Stok batch {$sourceBatch->batch_number} di pengirim tidak mencukupi ".
                        "(tersisa {$sourceBatch->jumlah}, diminta {$detail->jumlah}).",
                    );
                }
                $this->kurangiStokPengirimDistribusi($distribusi, $distDetail, $userId);
            }

            // 3. Update status DistribusiObat → 'diterima'
            $distribusi->update([
                'status' => 'diterima',
                'tanggal_terima' => $penerimaan->tanggal_penerimaan,
                'penerima_id' => $userId,
                'penerimaan_stok_id' => $penerimaan->id,
            ]);

            // 4. Update status PermintaanObat (jika ada) → 'diterima'
            if ($distribusi->permintaan) {
                $distribusi->permintaan->update([
                    'status' => 'diterima',
                    'tanggal_diterima' => $penerimaan->tanggal_penerimaan,
                ]);
            }
        });
    }

    /**
     * Reverse penerimaan: kembalikan stok ke kondisi sebelum penerimaan.
     */
    public function reversePenerimaan(PenerimaanStok $penerimaan, Collection $previousDetails): void
    {
        foreach ($previousDetails as $detail) {
            $reverseJumlah = -$detail->jumlah;

            $stok = $this->getStokTarget($penerimaan->fasilitas_id, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->increment('jumlah', $reverseJumlah);

            $this->kurangiBatch(
                obatId: $detail->obat_id,
                batchNumber: $detail->batch_number,
                tanggalExpired: $detail->tanggal_expired,
                fasilitasId: $penerimaan->fasilitas_id,
                jumlah: $detail->jumlah,
            );

            $this->catatRiwayat(
                fasilitasId: $penerimaan->fasilitas_id,
                obatId: $detail->obat_id,
                tipe: 'keluar',
                jumlah: $reverseJumlah,
                stokSebelum: $stokSebelum,
                referensi: $penerimaan,
                userId: $penerimaan->user_id,
                keterangan: 'Pembatalan penerimaan: '.$penerimaan->nomor_penerimaan,
                tanggal: now(),
            );

            // Safety net: sync aggregate from batch_stok
            if ($penerimaan->fasilitas_id) {
                BatchStok::recalculateFaskes($penerimaan->fasilitas_id, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }

        // Hapus realisasi anggaran jika reversal
        if ($penerimaan->sumber_dana_id) {
            SumberDanaPenggunaan::where('sumber_dana_id', $penerimaan->sumber_dana_id)
                ->where('rko_id', null)
                ->where('tipe', 'realisasi')
                ->where('keterangan', 'Penerimaan: '.$penerimaan->nomor_penerimaan)
                ->delete();
        }
    }

    /**
     * Proses opname selesai: update stok agregat, batch, dan catat riwayat.
     */
    public function prosesOpnameSelesai(OpnameStok $opname): void
    {
        foreach ($opname->details as $detail) {
            $selisih = $detail->selisih;

            if ($selisih === 0) {
                continue;
            }

            $stok = $this->getStokTarget($opname->fasilitas_id, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->increment('jumlah', $selisih);

            if ($selisih > 0 && $detail->batch_number && $detail->tanggal_expired) {
                $this->tambahBatch(
                    obatId: $detail->obat_id,
                    batchNumber: $detail->batch_number,
                    tanggalExpired: $detail->tanggal_expired,
                    fasilitasId: $opname->fasilitas_id,
                    jumlah: $selisih,
                    tanggalMasuk: $opname->tanggal_opname,
                );
            } elseif ($selisih < 0 && $detail->batch_number && $detail->tanggal_expired) {
                $this->kurangiBatch(
                    obatId: $detail->obat_id,
                    batchNumber: $detail->batch_number,
                    tanggalExpired: $detail->tanggal_expired,
                    fasilitasId: $opname->fasilitas_id,
                    jumlah: abs($selisih),
                );
            }

            $this->catatRiwayat(
                fasilitasId: $opname->fasilitas_id,
                obatId: $detail->obat_id,
                tipe: 'opname',
                jumlah: $selisih,
                stokSebelum: $stokSebelum,
                referensi: $opname,
                userId: $opname->user_id,
                keterangan: 'Opname: '.$opname->nomor_opname.' ('.$opname->tipe.')',
                tanggal: $opname->tanggal_opname,
            );

            // Safety net: sync aggregate from batch_stok
            if ($opname->fasilitas_id) {
                BatchStok::recalculateFaskes($opname->fasilitas_id, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }
    }

    /**
     * Reverse opname: kembalikan stok ke kondisi sebelum opname.
     */
    public function reverseOpname(OpnameStok $opname, Collection $previousDetails): void
    {
        foreach ($previousDetails as $detail) {
            $selisih = $detail->selisih;

            if ($selisih === 0) {
                continue;
            }

            $reverseJumlah = -$selisih;
            $stok = $this->getStokTarget($opname->fasilitas_id, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->increment('jumlah', $reverseJumlah);

            if ($selisih > 0 && $detail->batch_number && $detail->tanggal_expired) {
                $this->kurangiBatch(
                    obatId: $detail->obat_id,
                    batchNumber: $detail->batch_number,
                    tanggalExpired: $detail->tanggal_expired,
                    fasilitasId: $opname->fasilitas_id,
                    jumlah: $selisih,
                );
            }

            $this->catatRiwayat(
                fasilitasId: $opname->fasilitas_id,
                obatId: $detail->obat_id,
                tipe: 'penyesuaian',
                jumlah: $reverseJumlah,
                stokSebelum: $stokSebelum,
                referensi: $opname,
                userId: $opname->user_id,
                keterangan: 'Pembatalan opname: '.$opname->nomor_opname,
                tanggal: now(),
            );

            // Safety net: sync aggregate from batch_stok
            if ($opname->fasilitas_id) {
                BatchStok::recalculateFaskes($opname->fasilitas_id, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }
    }

    /**
     * Proses retur diterima: kurangi stok pengirim, tambah stok penerima, catat riwayat.
     *
     * Alur stok per tipe retur:
     * - pustu_ke_puskesmas: stok_faskes(pustu) --, stok_faskes(puskesmas) ++
     * - puskesmas_ke_gudang: stok_faskes(puskesmas) --, stok_gudang ++
     * - gudang_ke_supplier: stok_gudang --, tidak ada stok masuk
     */
    public function prosesReturDiterima(ReturObat $retur): void
    {
        foreach ($retur->details as $detail) {
            $jumlah = $detail->jumlah_retur;

            // 1. Kurangi stok pengirim
            $this->kurangiStokRetur(
                retur: $retur,
                obatId: $detail->obat_id,
                batchId: $detail->batch_id,
                jumlah: $jumlah,
                isPengirim: true,
            );

            // 2. Tambah stok penerima (kecuali gudang_ke_supplier)
            if ($retur->tipe_retur !== 'gudang_ke_supplier') {
                $this->tambahStokPenerimaRetur(
                    retur: $retur,
                    detail: $detail,
                    jumlah: $jumlah,
                );
            }
        }
    }

    /**
     * Proses pemakaian obat (header+detail): untuk setiap detail, kurangi stok_faskes (atau stok_gudang),
     * kurangi batch_stok (jika ada), catat RiwayatStok.
     *
     * Alur per detail:
     * - Pemakaian di faskes (fasilitas_id != null): kurangi stok_faskes + batch_stok(faskes) → catat riwayat tipe 'keluar'
     * - Pemakaian di gudang (fasilitas_id == null): kurangi stok_gudang + batch_stok(gudang) → catat riwayat tipe 'keluar'
     *
     * Polymorphic ref riwayat_stok: merujuk ke DetailPemakaianObat (1 row per detail).
     */
    public function prosesPemakaian(PemakaianObat $pemakaian): void
    {
        DB::transaction(function () use ($pemakaian): void {
            $this->prosesPemakaianDalamTransaksi($pemakaian);
        });
    }

    private function prosesPemakaianDalamTransaksi(PemakaianObat $pemakaian): void
    {
        $fasilitasId = $pemakaian->fasilitas_id;

        // Pastikan details sudah ter-load (avoid lazy-load N+1)
        $details = $pemakaian->relationLoaded('details')
            ? $pemakaian->details
            : $pemakaian->details()->get();

        $batches = $this->lockAndValidateBatchPemakaian($pemakaian, $details, $fasilitasId);

        foreach ($details as $detail) {
            // 1. Kurangi stok agregat (per-obat, per-detail)
            $stok = $this->getStokTarget($fasilitasId, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->decrement('jumlah', $detail->jumlah);

            // 2. Kurangi batch_stok yang telah divalidasi dan dikunci.
            $batch = $batches->get($detail->batch_id);
            $batch->decrement('jumlah', $detail->jumlah);

            if ($batch->jumlah <= 0) {
                $batch->update(['status' => 'dimusnahkan']);
            }

            // 3. Catat RiwayatStok tipe 'keluar' (1 row per detail, polymorphic ref ke detail)
            $this->catatRiwayat(
                fasilitasId: $fasilitasId,
                obatId: $detail->obat_id,
                tipe: 'keluar',
                jumlah: -$detail->jumlah,
                stokSebelum: $stokSebelum,
                referensi: $detail,
                userId: $pemakaian->user_id,
                keterangan: 'Pemakaian obat: '.$pemakaian->nomor_pemakaian.' ('.$pemakaian->jenis_pelayanan_label.')',
                tanggal: $pemakaian->tanggal_pemakaian,
            );

            // 4. Safety net: sync aggregate from batch_stok
            if ($fasilitasId) {
                BatchStok::recalculateFaskes($fasilitasId, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }
    }

    /**
     * Reverse pemakaian: kembalikan stok ke kondisi sebelum pemakaian untuk setiap detail.
     *
     * Dipanggil saat:
     * - Delete record pemakaian
     * - Edit record pemakaian (sebelum prosesPemakaian ulang dengan data baru)
     */
    public function reversePemakaian(PemakaianObat $pemakaian): void
    {
        DB::transaction(function () use ($pemakaian): void {
            $this->reversePemakaianDalamTransaksi($pemakaian);
        });
    }

    private function reversePemakaianDalamTransaksi(PemakaianObat $pemakaian): void
    {
        $fasilitasId = $pemakaian->fasilitas_id;

        // Pastikan details sudah ter-load (avoid lazy-load N+1)
        $details = $pemakaian->relationLoaded('details')
            ? $pemakaian->details
            : $pemakaian->details()->get();

        foreach ($details as $detail) {
            // 1. Tambah kembali stok agregat (per-obat, per-detail)
            $stok = $this->getStokTarget($fasilitasId, $detail->obat_id);
            $stokSebelum = $stok->jumlah;
            $stok->increment('jumlah', $detail->jumlah);

            // 2. Tambah kembali batch_stok jika ada batch_id
            if ($detail->batch_id) {
                $batch = BatchStok::query()
                    ->lockForUpdate()
                    ->find($detail->batch_id);

                if ($batch) {
                    $batch->increment('jumlah', $detail->jumlah);

                    // Re-aktifkan batch jika sebelumnya berstatus 'dimusnahkan' karena stok habis
                    if ($batch->status === 'dimusnahkan' && $batch->jumlah > 0) {
                        $batch->update(['status' => 'tersedia']);
                    }
                }
            }

            // 3. Catat RiwayatStok reversal (tipe 'masuk' dengan jumlah positif, polymorphic ref ke detail)
            $this->catatRiwayat(
                fasilitasId: $fasilitasId,
                obatId: $detail->obat_id,
                tipe: 'masuk',
                jumlah: $detail->jumlah,
                stokSebelum: $stokSebelum,
                referensi: $detail,
                userId: $pemakaian->user_id,
                keterangan: 'Pembatalan pemakaian: '.$pemakaian->nomor_pemakaian.' ('.$pemakaian->jenis_pelayanan_label.')',
                tanggal: now(),
            );

            // 4. Safety net: sync aggregate from batch_stok
            if ($fasilitasId) {
                BatchStok::recalculateFaskes($fasilitasId, $detail->obat_id);
            } else {
                BatchStok::recalculateGudang($detail->obat_id);
            }
        }
    }

    /**
     * @param  Collection<int, DetailPemakaianObat>  $details
     * @return Collection<int, BatchStok>
     */
    private function lockAndValidateBatchPemakaian(
        PemakaianObat $pemakaian,
        Collection $details,
        ?int $fasilitasId,
    ): Collection {
        $batchIds = $details->pluck('batch_id')->filter()->unique()->values();

        if ($batchIds->count() !== $details->count()) {
            throw new \RuntimeException('Setiap detail pemakaian harus memiliki batch stok.');
        }

        $batches = BatchStok::query()
            ->whereKey($batchIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $jumlahPerBatch = $details->groupBy('batch_id')->map->sum('jumlah');

        foreach ($details as $detail) {
            $batch = $batches->get($detail->batch_id);

            if (! $batch) {
                throw new \RuntimeException("Batch stok ID {$detail->batch_id} tidak ditemukan untuk pemakaian {$pemakaian->nomor_pemakaian}.");
            }

            if (
                $batch->status !== 'tersedia'
                || $batch->obat_id !== $detail->obat_id
                || $batch->fasilitas_id !== $fasilitasId
            ) {
                throw new \RuntimeException("Batch stok {$batch->batch_number} tidak sesuai dengan detail pemakaian.");
            }
        }

        foreach ($jumlahPerBatch as $batchId => $jumlah) {
            $batch = $batches->get($batchId);

            if ($batch->jumlah < $jumlah) {
                throw new \RuntimeException(
                    "Stok batch {$batch->batch_number} tidak mencukupi (tersisa {$batch->jumlah}, diminta {$jumlah}).",
                );
            }
        }

        return $batches;
    }

    /**
     * Tambah stok penerima saat distribusi diterima.
     *
     * Alur: tambah/create batch_stok penerima → update stok_faskes → catat riwayat.
     */
    public function tambahStokPenerimaDistribusi(
        DistribusiObat $distribusi,
        DetailDistribusiObat $detail,
        int $userId,
    ): void {
        // 1. Tambah/create batch_stok penerima (obat_id + batch_number + fasilitas_penerima)
        $batch = $this->tambahBatch(
            obatId: $detail->obat_id,
            batchNumber: $detail->batch->batch_number,
            tanggalExpired: $detail->batch->tanggal_expired,
            fasilitasId: $distribusi->fasilitas_penerima_id,
            jumlah: $detail->jumlah,
            tanggalMasuk: now(),
        );

        // Cascade sumber_dana_id dari batch asal (pengirim) ke batch penerima
        if ($sumberDanaId = $detail->batch?->sumber_dana_id) {
            $batch->sumber_dana_id = $sumberDanaId;
            $batch->save();
        }

        // 2. Update stok_faskes (penerima selalu faskes)
        $stok = $this->getStokTarget($distribusi->fasilitas_penerima_id, $detail->obat_id);
        $stokSebelum = $stok->jumlah;
        $stok->increment('jumlah', $detail->jumlah);

        // 3. Catat riwayat stok
        $this->catatRiwayat(
            fasilitasId: $distribusi->fasilitas_penerima_id,
            obatId: $detail->obat_id,
            tipe: 'distribusi_masuk',
            jumlah: $detail->jumlah,
            stokSebelum: $stokSebelum,
            referensi: $distribusi,
            userId: $userId,
            keterangan: 'Distribusi '.$distribusi->nomor_surat_jalan,
            tanggal: now(),
        );

        // 4. Safety net: sync aggregate from batch_stok
        BatchStok::recalculateFaskes($distribusi->fasilitas_penerima_id, $detail->obat_id);
    }

    /**
     * Kurangi stok pengirim saat distribusi diterima.
     *
     * Alur: kurangi batch_stok pengirim → kurangi stok_faskes/stok_gudang → catat riwayat.
     * Fixes bug: stok_gudang tidak ter-decrement saat pengirim adalah gudang (dinas_ke_puskesmas).
     */
    public function kurangiStokPengirimDistribusi(
        DistribusiObat $distribusi,
        DetailDistribusiObat $detail,
        int $userId,
    ): void {
        // 1. Kurangi batch_stok pengirim
        $batch = BatchStok::find($detail->batch_id);

        if (! $batch) {
            throw new \RuntimeException(
                "Batch stok ID {$detail->batch_id} tidak ditemukan untuk distribusi {$distribusi->nomor_surat_jalan}.",
            );
        }

        if ($batch->jumlah < $detail->jumlah) {
            throw new \RuntimeException(
                "Stok batch {$batch->batch_number} di pengirim tidak mencukupi ".
                "(tersisa {$batch->jumlah}, diminta {$detail->jumlah}) untuk distribusi {$distribusi->nomor_surat_jalan}.",
            );
        }

        $fasilitasId = $distribusi->fasilitas_pengirim_id;
        $stok = $this->getStokTarget($fasilitasId, $detail->obat_id);

        $stokSebelum = $stok->jumlah;
        $batch->decrement('jumlah', $detail->jumlah);

        // Update status batch jika stok habis
        if ($batch->jumlah <= 0) {
            $batch->update(['status' => 'dimusnahkan']);
        }

        $stok->decrement('jumlah', $detail->jumlah);

        // 3. Catat riwayat stok
        $this->catatRiwayat(
            fasilitasId: $fasilitasId,
            obatId: $detail->obat_id,
            tipe: 'distribusi_keluar',
            jumlah: -$detail->jumlah,
            stokSebelum: $stokSebelum,
            referensi: $distribusi,
            userId: $userId,
            keterangan: 'Distribusi '.$distribusi->nomor_surat_jalan,
            tanggal: now(),
        );

        // 4. Safety net: sync aggregate from batch_stok
        if ($fasilitasId) {
            BatchStok::recalculateFaskes($fasilitasId, $detail->obat_id);
        } else {
            BatchStok::recalculateGudang($detail->obat_id);
        }
    }

    /**
     * Kurangi atau tambah stok untuk retur berdasarkan apakah pengirim atau penerima.
     */
    private function kurangiStokRetur(
        ReturObat $retur,
        int $obatId,
        ?int $batchId,
        int $jumlah,
        bool $isPengirim,
    ): void {
        // Tentukan fasilitas_id berdasarkan tipe retur dan role (pengirim/penerima)
        $fasilitasId = $this->getFasilitasIdForRetur($retur, $isPengirim);

        // Update batch_stok — only if batch exists AND has sufficient stock
        if ($batchId) {
            $batch = BatchStok::find($batchId);
            if ($batch && $batch->jumlah >= $jumlah) {
                $stokSebelum = $batch->jumlah;
                $batch->decrement('jumlah', $jumlah);

                // Update status batch jika stok habis
                if ($batch->jumlah <= 0) {
                    $batch->update(['status' => 'dimusnahkan']);
                }

                // Catat riwayat stok batch
                $this->catatRiwayat(
                    fasilitasId: $fasilitasId,
                    obatId: $obatId,
                    tipe: 'keluar',
                    jumlah: -$jumlah,
                    stokSebelum: $stokSebelum,
                    referensi: $retur,
                    userId: auth()->id(),
                    keterangan: 'Retur: '.$retur->nomor_retur.' (pengirim)',
                    tanggal: now(),
                );
            }
        }

        // Update stok agregat (stok_faskes atau stok_gudang)
        $stok = $this->getStokTarget($fasilitasId, $obatId);
        $stokSebelum = $stok->jumlah;
        $stok->increment('jumlah', -$jumlah);

        // Catat riwayat stok untuk stok agregat
        $this->catatRiwayat(
            fasilitasId: $fasilitasId,
            obatId: $obatId,
            tipe: 'keluar',
            jumlah: -$jumlah,
            stokSebelum: $stokSebelum,
            referensi: $retur,
            userId: auth()->id(),
            keterangan: 'Retur: '.$retur->nomor_retur.' (pengirim)',
            tanggal: now(),
        );

        // Safety net: sync aggregate from batch_stok
        if ($fasilitasId) {
            BatchStok::recalculateFaskes($fasilitasId, $obatId);
        } else {
            BatchStok::recalculateGudang($obatId);
        }
    }

    private function tambahStokPenerimaRetur(
        ReturObat $retur,
        DetailReturObat $detail,
        int $jumlah,
    ): void {
        $fasilitasId = $this->getFasilitasIdForRetur($retur, isPengirim: false);

        // Update batch_stok penerima
        if ($detail->batch_id) {
            $originalBatch = BatchStok::find($detail->batch_id);

            if ($originalBatch) {
                // Cari batch_stok penerima dengan batch_number + obat_id yang sama
                $targetBatch = BatchStok::firstOrNew(
                    [
                        'fasilitas_id' => $fasilitasId,
                        'obat_id' => $detail->obat_id,
                        'batch_number' => $originalBatch->batch_number,
                    ],
                    [
                        'tanggal_expired' => $originalBatch->tanggal_expired,
                        'status' => 'tersedia',
                        'tanggal_masuk' => now(),
                    ],
                );

                $stokSebelum = (int) $targetBatch->jumlah;

                if (! $targetBatch->exists) {
                    $targetBatch->save();
                }

                $targetBatch->increment('jumlah', $jumlah);

                // Catat riwayat stok batch
                $this->catatRiwayat(
                    fasilitasId: $fasilitasId,
                    obatId: $detail->obat_id,
                    tipe: 'masuk',
                    jumlah: $jumlah,
                    stokSebelum: $stokSebelum,
                    referensi: $retur,
                    userId: auth()->id(),
                    keterangan: 'Retur: '.$retur->nomor_retur.' (penerima)',
                    tanggal: now(),
                );
            }
        }

        // Safety net: sync aggregate from batch_stok
        if ($fasilitasId) {
            BatchStok::recalculateFaskes($fasilitasId, $detail->obat_id);
        } else {
            BatchStok::recalculateGudang($detail->obat_id);
        }
    }

    /**
     * Tentukan fasilitas_id untuk retur berdasarkan tipe dan role.
     */
    private function getFasilitasIdForRetur(ReturObat $retur, bool $isPengirim): ?int
    {
        return match (true) {
            // pustu_ke_puskesmas: pengirim = pustu, penerima = puskesmas
            $retur->tipe_retur === 'pustu_ke_puskesmas' && $isPengirim => $retur->fasilitas_pengirim_id,
            $retur->tipe_retur === 'pustu_ke_puskesmas' && ! $isPengirim => $retur->fasilitas_penerima_id,

            // puskesmas_ke_gudang: pengirim = puskesmas, penerima = gudang (NULL)
            $retur->tipe_retur === 'puskesmas_ke_gudang' && $isPengirim => $retur->fasilitas_pengirim_id,
            $retur->tipe_retur === 'puskesmas_ke_gudang' && ! $isPengirim => null,

            // gudang_ke_supplier: pengirim = gudang (NULL), penerima = tidak ada
            $retur->tipe_retur === 'gudang_ke_supplier' && $isPengirim => null,

            default => null,
        };
    }

    /**
     * Catat riwayat stok ke tabel riwayat_stok.
     */
    private function catatRiwayat(
        ?int $fasilitasId,
        int $obatId,
        string $tipe,
        int $jumlah,
        int $stokSebelum,
        object $referensi,
        int $userId,
        string $keterangan,
        mixed $tanggal,
    ): RiwayatStok {
        return RiwayatStok::create([
            'fasilitas_id' => $fasilitasId,
            'obat_id' => $obatId,
            'tipe' => $tipe,
            'jumlah' => $jumlah,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSebelum + $jumlah,
            'referensi_type' => get_class($referensi),
            'referensi_id' => $referensi->id,
            'user_id' => $userId,
            'keterangan' => $keterangan,
            'tanggal' => $tanggal,
        ]);
    }

    private function getStokTarget(?int $fasilitasId, int $obatId): StokGudang|StokFaskes
    {
        if ($fasilitasId) {
            return StokFaskes::firstOrCreate(
                ['fasilitas_id' => $fasilitasId, 'obat_id' => $obatId],
                ['jumlah' => 0, 'stok_minimum' => 0],
            );
        }

        return StokGudang::firstOrCreate(
            ['obat_id' => $obatId],
            ['jumlah' => 0, 'stok_minimum' => 0],
        );
    }

    private function prosesBatchDariPenerimaan(DetailPenerimaanStok $detail, PenerimaanStok $penerimaan): BatchStok
    {
        $batch = BatchStok::firstOrNew([
            'obat_id' => $detail->obat_id,
            'batch_number' => $detail->batch_number,
            'tanggal_expired' => $detail->tanggal_expired,
            'fasilitas_id' => $penerimaan->fasilitas_id,
        ]);

        if (! $batch->exists) {
            $batch->fill([
                'penerimaan_id' => $penerimaan->id,
                'jumlah' => $detail->jumlah,
                'status' => 'tersedia',
                'tanggal_masuk' => $penerimaan->tanggal_penerimaan,
                'harga_beli' => $detail->harga_satuan,
                'sumber_dana_id' => $penerimaan->sumber_dana_id,
            ]);
            $batch->save();
        } else {
            $batch->increment('jumlah', $detail->jumlah);
        }

        return $batch;
    }

    private function tambahBatch(
        int $obatId,
        string $batchNumber,
        mixed $tanggalExpired,
        ?int $fasilitasId,
        int $jumlah,
        mixed $tanggalMasuk,
    ): BatchStok {
        $batch = BatchStok::firstOrNew([
            'obat_id' => $obatId,
            'batch_number' => $batchNumber,
            'tanggal_expired' => $tanggalExpired,
            'fasilitas_id' => $fasilitasId,
        ]);

        if (! $batch->exists) {
            $batch->fill([
                'jumlah' => $jumlah,
                'status' => 'tersedia',
                'tanggal_masuk' => $tanggalMasuk,
            ]);
            $batch->save();
        } else {
            $batch->increment('jumlah', $jumlah);
        }

        return $batch;
    }

    private function kurangiBatch(
        int $obatId,
        string $batchNumber,
        mixed $tanggalExpired,
        ?int $fasilitasId,
        int $jumlah,
    ): void {
        $batch = BatchStok::query()
            ->where('obat_id', $obatId)
            ->where('batch_number', $batchNumber)
            ->whereDate('tanggal_expired', $tanggalExpired)
            ->where('fasilitas_id', $fasilitasId)
            ->first();

        $batch?->decrement('jumlah', $jumlah);
    }
}
