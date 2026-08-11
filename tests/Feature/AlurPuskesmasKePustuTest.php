<?php

namespace Tests\Feature;

use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPermintaanObat;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\FefoService;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurPuskesmasKePustuTest extends TestCase
{
    use RefreshDatabase;

    private User $userPuskesmas;

    private User $userPustu;

    private User $superAdmin;

    private FasilitasKesehatan $puskesmas;

    private FasilitasKesehatan $pustu;

    private Obat $obatA;

    private Obat $obatB;

    private string $batchNumber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-001',
            'nama' => 'Puskesmas Induk',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Puskesmas',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-001',
            'nama' => 'Pustu Bawahan',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmas->id,
            'alamat' => 'Jl. Pustu',
            'pic' => 'Test PIC Pustu',
            'kontak_pic' => '1234',
            'status' => 'aktif',
        ]);

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Puskesmas',
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->userPustu = User::factory()->create([
            'name' => 'User Pustu',
            'fasilitas_kesehatan_id' => $this->pustu->id,
        ]);
        $this->userPustu->assignRole('pustu');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->obatA = Obat::factory()->create();
        $this->obatB = Obat::factory()->create();
        $this->batchNumber = 'BCH-'.uniqid();
    }

    /**
     * Buat stok awal di puskesmas (pengirim).
     */
    private function makeStokPuskesmas(Obat $obat, int $jumlah = 100, ?string $batch = null): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $obat->id,
            'fasilitas_id' => $this->puskesmas->id,
            'batch_number' => $batch ?? $this->batchNumber,
            'tanggal_expired' => now()->addYear()->toDateString(),
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => now()->toDateString(),
        ]);
    }

    /**
     * Buat permintaan dari pustu ke puskesmas.
     */
    private function makePermintaan(array $items): PermintaanObat
    {
        $permintaan = PermintaanObat::create([
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        foreach ($items as $item) {
            $permintaan->details()->create([
                'obat_id' => $item['obat']->id,
                'jumlah_diminta' => $item['jumlah'],
            ]);
        }

        return $permintaan->fresh(['details']);
    }

    /**
     * Approve permintaan (oleh puskesmas sebagai penerima).
     */
    private function approvePermintaan(PermintaanObat $permintaan): void
    {
        $permintaan->update([
            'status' => 'disetujui',
            'disetujui_oleh' => $this->userPuskesmas->id,
            'tanggal_disetujui' => now()->toDateString(),
        ]);
    }

    /**
     * Buat distribusi puskesmas → pustu, dengan alokasi FEFO.
     * Mirip dengan CreateDistribusiObat::handleRecordCreation.
     */
    private function makeDistribusi(
        PermintaanObat $permintaan,
        array $items,
        string $status = 'draft',
    ): DistribusiObat {
        $fefo = app(FefoService::class);

        foreach ($items as $item) {
            if (! $fefo->hasSufficientStock($item['obat']->id, $item['jumlah'], $this->puskesmas->id)) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi untuk {$item['obat']->nama_obat}: diminta {$item['jumlah']}.",
                );
            }
        }

        $distribusi = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.now()->format('Y').'/TEST-'.uniqid(),
            'permintaan_id' => $permintaan->id,
            'tipe_distribusi' => 'puskesmas_ke_pustu',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_penerima_id' => $this->pustu->id,
            'status' => $status,
            'pengirim_id' => $this->userPuskesmas->id,
        ]);

        foreach ($items as $item) {
            $allocation = $fefo->allocate(
                $item['obat']->id,
                $item['jumlah'],
                $this->puskesmas->id,
            );

            foreach ($allocation as $alloc) {
                $distribusi->details()->create([
                    'obat_id' => $item['obat']->id,
                    'batch_id' => $alloc['batch_id'],
                    'jumlah' => $alloc['jumlah'],
                ]);
            }
        }

        return $distribusi->fresh(['details', 'details.batch']);
    }

    /**
     * Kirim distribusi (set status → dalam_pengiriman + update jumlah_dikirim).
     * Mirip tombol Kirim di EditDistribusiObat / DetailDistribusi.
     */
    private function kirimDistribusi(DistribusiObat $distribusi): void
    {
        // 1. Update status & tanggal_kirim
        $distribusi->update([
            'status' => 'dalam_pengiriman',
            'tanggal_kirim' => now()->toDateString(),
        ]);

        // 2. Update jumlah_dikirim di detail permintaan
        $totals = DetailDistribusiObat::whereHas('distribusi', fn ($q) => $q->where('permintaan_id', $distribusi->permintaan_id))
            ->selectRaw('obat_id, SUM(jumlah) as total_jumlah')
            ->groupBy('obat_id')
            ->pluck('total_jumlah', 'obat_id');

        foreach ($totals as $obatId => $totalJumlah) {
            DetailPermintaanObat::where('permintaan_id', $distribusi->permintaan_id)
                ->where('obat_id', $obatId)
                ->update(['jumlah_dikirim' => $totalJumlah]);
        }

        // 3. Update status permintaan
        if ($distribusi->permintaan) {
            $distribusi->permintaan->update(['status' => 'sedang_didistribusi']);
        }
    }

    /**
     * Batalkan pengiriman (kembali ke draft + reset jumlah_dikirim).
     * Mirip tombol Batalkan di EditDistribusiObat.
     */
    private function batalkanDistribusi(DistribusiObat $distribusi): void
    {
        $distribusi->update([
            'status' => 'draft',
            'tanggal_kirim' => null,
        ]);

        if ($distribusi->permintaan) {
            $distribusi->permintaan->update(['status' => 'disetujui']);

            DetailPermintaanObat::where('permintaan_id', $distribusi->permintaan_id)
                ->update(['jumlah_dikirim' => null]);
        }
    }

    /**
     * Tolak distribusi (oleh penerima / super_admin).
     * Mirip tombol Tolak di DetailDistribusi / EditDistribusiObat.
     */
    private function tolakDistribusi(DistribusiObat $distribusi, User $user, string $catatan = 'Barang tidak sesuai'): void
    {
        $distribusi->update([
            'status' => 'ditolak',
            'tanggal_ditolak' => now()->toDateString(),
            'penerima_id' => $user->id,
            'catatan' => $catatan,
        ]);

        if ($distribusi->permintaan) {
            $distribusi->permintaan->update([
                'status' => 'disetujui',
                'tanggal_diterima' => null,
            ]);

            DetailPermintaanObat::where('permintaan_id', $distribusi->permintaan_id)
                ->update(['jumlah_dikirim' => null]);
        }
    }

    /**
     * Buat penerimaan + konfirmasi (prosesPenerimaanDistribusi).
     */
    private function terimaDistribusi(DistribusiObat $distribusi, User $user): PenerimaanStok
    {
        $penerimaan = PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/TEST-'.uniqid(),
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusi->id,
            'fasilitas_id' => $distribusi->fasilitas_penerima_id,
            'user_id' => $user->id,
            'status' => 'dikonfirmasi',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        foreach ($distribusi->details as $distDetail) {
            $penerimaan->details()->create([
                'obat_id' => $distDetail->obat_id,
                'batch_number' => $distDetail->batch->batch_number,
                'tanggal_expired' => $distDetail->batch->tanggal_expired,
                'jumlah' => $distDetail->jumlah,
                'harga_satuan' => 1000,
                'sub_total' => $distDetail->jumlah * 1000,
            ]);
        }

        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);

        return $penerimaan->fresh();
    }

    // ──────────────────────────────────────────────
    //  FULL E2E HAPPY PATH: permintaan → distribusi → kirim → terima
    // ──────────────────────────────────────────────

    public function test_full_flow_pustu_permintaan_to_penerimaan(): void
    {
        $this->makeStokPuskesmas($this->obatA, 100);
        $this->makeStokPuskesmas($this->obatB, 50, 'BCH-002');

        $permintaan = $this->makePermintaan([
            ['obat' => $this->obatA, 'jumlah' => 30],
            ['obat' => $this->obatB, 'jumlah' => 20],
        ]);
        $this->assertSame('menunggu_persetujuan', $permintaan->status);

        $this->approvePermintaan($permintaan);
        $this->assertSame('disetujui', $permintaan->fresh()->status);

        $distribusi = $this->makeDistribusi($permintaan, [
            ['obat' => $this->obatA, 'jumlah' => 30],
            ['obat' => $this->obatB, 'jumlah' => 20],
        ]);
        $this->assertSame('draft', $distribusi->status);
        $this->assertCount(2, $distribusi->details);

        $this->kirimDistribusi($distribusi);
        $distribusi->refresh();

        $this->assertSame('dalam_pengiriman', $distribusi->status);
        $this->assertNotNull($distribusi->tanggal_kirim);
        $this->assertSame('sedang_didistribusi', $permintaan->fresh()->status);

        $penerimaan = $this->terimaDistribusi($distribusi, $this->userPustu);
        $this->assertSame('dikonfirmasi', $penerimaan->status);

        $distribusi->refresh();
        $permintaan->refresh();

        $this->assertSame('diterima', $distribusi->status);
        $this->assertSame('diterima', $permintaan->status);
        $this->assertNotNull($distribusi->tanggal_terima);
        $this->assertSame($this->userPustu->id, (int) $distribusi->penerima_id);
        $this->assertSame((int) $penerimaan->id, (int) $distribusi->penerimaan_stok_id);
    }

    // ──────────────────────────────────────────────
    //  STOCK MUTATION: puskesmas → pustu
    // ──────────────────────────────────────────────

    public function test_stock_mutation_puskesmas_to_pustu(): void
    {
        $batch = $this->makeStokPuskesmas($this->obatA, 100);
        $this->assertSame(100, (int) $batch->jumlah);

        $this->assertDatabaseMissing('stok_faskes', [
            'fasilitas_id' => $this->pustu->id,
            'obat_id' => $this->obatA->id,
        ]);

        $permintaan = $this->makePermintaan([['obat' => $this->obatA, 'jumlah' => 30]]);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, [['obat' => $this->obatA, 'jumlah' => 30]]);
        $this->kirimDistribusi($distribusi);
        $this->terimaDistribusi($distribusi, $this->userPustu);

        $batch->refresh();
        $this->assertSame(70, (int) $batch->jumlah);

        $stokPustu = StokFaskes::where('fasilitas_id', $this->pustu->id)
            ->where('obat_id', $this->obatA->id)
            ->first();
        $this->assertNotNull($stokPustu);
        $this->assertSame(30, (int) $stokPustu->jumlah);
    }

    // ──────────────────────────────────────────────
    //  CANCEL (batalkan): draft → dikirim → batal → draft
    // ──────────────────────────────────────────────

    public function test_cancel_distribusi_resets_status_and_jumlah_dikirim(): void
    {
        $this->makeStokPuskesmas($this->obatA, 50);

        $permintaan = $this->makePermintaan([['obat' => $this->obatA, 'jumlah' => 10]]);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, [['obat' => $this->obatA, 'jumlah' => 10]]);
        $this->kirimDistribusi($distribusi);

        // Verify sebelum batal
        $this->assertSame('sedang_didistribusi', $permintaan->fresh()->status);
        $this->assertSame(10, (int) $permintaan->fresh()->details->first()->jumlah_dikirim);

        // Batalkan
        $this->batalkanDistribusi($distribusi);

        $distribusi->refresh();
        $permintaan->refresh();

        $this->assertSame('draft', $distribusi->status);
        $this->assertNull($distribusi->tanggal_kirim);
        $this->assertSame('disetujui', $permintaan->status);
        $this->assertNull($permintaan->details->first()->jumlah_dikirim);
    }

    // ──────────────────────────────────────────────
    //  REJECT (tolak): penerima menolak distribusi
    // ──────────────────────────────────────────────

    public function test_reject_distribusi_by_penerima(): void
    {
        $this->makeStokPuskesmas($this->obatA, 50);

        $permintaan = $this->makePermintaan([['obat' => $this->obatA, 'jumlah' => 10]]);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, [['obat' => $this->obatA, 'jumlah' => 10]]);
        $this->kirimDistribusi($distribusi);

        // Tolak oleh pustu (penerima)
        $this->tolakDistribusi($distribusi, $this->userPustu, 'Stok masih cukup');

        $distribusi->refresh();
        $permintaan->refresh();

        $this->assertSame('ditolak', $distribusi->status);
        $this->assertNotNull($distribusi->tanggal_ditolak);
        $this->assertSame('Stok masih cukup', $distribusi->catatan);
        $this->assertSame($this->userPustu->id, (int) $distribusi->penerima_id);

        // Permintaan kembali ke disetujui + jumlah_dikirim direset
        $this->assertSame('disetujui', $permintaan->status);
        $this->assertNull($permintaan->details->first()->jumlah_dikirim);
    }

    // ──────────────────────────────────────────────
    //  STOCK VALIDATION: gagal jika stok tidak cukup
    // ──────────────────────────────────────────────

    public function test_distribusi_fails_when_insufficient_stock(): void
    {
        $this->makeStokPuskesmas($this->obatA, 5);

        $permintaan = $this->makePermintaan([['obat' => $this->obatA, 'jumlah' => 10]]);
        $this->approvePermintaan($permintaan);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stok tidak mencukupi|insufficient/');

        $this->makeDistribusi($permintaan, [['obat' => $this->obatA, 'jumlah' => 10]]);
    }

    // ──────────────────────────────────────────────
    //  RE-DISTRIBUTE AFTER REJECT: bisa kirim ulang dari permintaan yang sama
    // ──────────────────────────────────────────────

    public function test_redistribute_after_reject(): void
    {
        $this->makeStokPuskesmas($this->obatA, 100);

        $permintaan = $this->makePermintaan([['obat' => $this->obatA, 'jumlah' => 20]]);
        $this->approvePermintaan($permintaan);

        $distribusi1 = $this->makeDistribusi($permintaan, [['obat' => $this->obatA, 'jumlah' => 20]]);
        $this->kirimDistribusi($distribusi1);
        $this->tolakDistribusi($distribusi1, $this->userPustu, 'Salah obat');

        // Permintaan kembali ke disetujui, bisa distribusi ulang
        $distribusi2 = $this->makeDistribusi($permintaan->fresh(), [['obat' => $this->obatA, 'jumlah' => 20]]);
        $this->kirimDistribusi($distribusi2);
        $this->terimaDistribusi($distribusi2, $this->userPustu);

        $this->assertSame('diterima', $distribusi2->fresh()->status);
        $this->assertSame('diterima', $permintaan->fresh()->status);
    }

    // ──────────────────────────────────────────────
    //  MULTI-ITEM dengan MULTI-BATCH alokasi FEFO
    // ──────────────────────────────────────────────

    public function test_multi_item_multi_batch_distribusi(): void
    {
        // Obat A: 2 batch (40 + 60 = 100)
        $this->makeStokPuskesmas($this->obatA, 40, 'BCH-A1');
        $this->makeStokPuskesmas($this->obatA, 60, 'BCH-A2');
        // Obat B: 1 batch (30)
        $this->makeStokPuskesmas($this->obatB, 30, 'BCH-B1');

        $permintaan = $this->makePermintaan([
            ['obat' => $this->obatA, 'jumlah' => 70],
            ['obat' => $this->obatB, 'jumlah' => 15],
        ]);
        $this->approvePermintaan($permintaan);

        $distribusi = $this->makeDistribusi($permintaan, [
            ['obat' => $this->obatA, 'jumlah' => 70],
            ['obat' => $this->obatB, 'jumlah' => 15],
        ]);

        // FEFO: obat A harus diambil dari batch BCH-A1 dulu (expired-nya sama, urut ascending)
        $detailA = $distribusi->details->where('obat_id', $this->obatA->id);
        $this->assertGreaterThan(0, $detailA->count());

        $totalA = $detailA->sum('jumlah');
        $this->assertSame(70, (int) $totalA);

        $totalB = $distribusi->details->where('obat_id', $this->obatB->id)->sum('jumlah');
        $this->assertSame(15, (int) $totalB);

        $this->kirimDistribusi($distribusi);
        $this->terimaDistribusi($distribusi, $this->userPustu);

        $this->assertSame('diterima', $distribusi->fresh()->status);
    }
}
