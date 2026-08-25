<?php

namespace Tests\Unit;

use App\Models\BatchStok;
use App\Models\DetailPemakaianObat;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokServiceTest extends TestCase
{
    use RefreshDatabase;

    private StokService $stokService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stokService = app(StokService::class);
    }

    private function makeObat(): Obat
    {
        return Obat::create([
            'kode_obat' => 'OBT-TEST-'.uniqid(),
            'nama_obat' => 'Obat Test',
            'kategori' => 'Analgesik',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'status' => 'aktif',
            'harga_satuan' => 1000,
            'metode_stok' => 'fefo',
        ]);
    }

    private function makeFaskes(): FasilitasKesehatan
    {
        return FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-TEST-'.uniqid(),
            'nama' => 'Puskesmas Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);
    }

    private function makeBatch(int $obatId, ?int $fasilitasId, int $jumlah, string $batchNumber = 'BCH-001'): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $obatId,
            'fasilitas_id' => $fasilitasId,
            'batch_number' => $batchNumber,
            'tanggal_expired' => now()->addYear(),
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => now(),
        ]);
    }

    private function makePemakaian(?int $fasilitasId, int $userId, array $detailData): PemakaianObat
    {
        if (! User::find($userId)) {
            User::factory()->create(['id' => $userId, 'name' => 'User '.$userId]);
        }

        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-TEST-'.uniqid(),
            'fasilitas_id' => $fasilitasId,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $userId,
        ]);

        foreach ($detailData as $detail) {
            $pemakaian->details()->create($detail);
        }

        return $pemakaian->fresh('details');
    }

    // ──────────────────────────────────────────────
    //  PROSES PEMAKAIAN (stok berkurang)
    // ──────────────────────────────────────────────

    public function test_proses_pemakaian_mengurangi_stok_faskes(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);

        $stokAwal = StokFaskes::recalculateForObat($faskes->id, $obat->id);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 30],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);

        $stokFaskes = StokFaskes::where('fasilitas_id', $faskes->id)
            ->where('obat_id', $obat->id)
            ->first();
        $batchBaru = $batch->fresh();

        $this->assertEquals(70, $stokFaskes->jumlah);
        $this->assertEquals(70, $batchBaru->jumlah);
        $this->assertEquals(30, RiwayatStok::where('referensi_id', $pemakaian->details->first()->id)->sum('jumlah') * -1);
    }

    public function test_proses_pemakaian_mengurangi_stok_faskes_menghapus_batch_habis(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 50);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 50],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);

        $this->assertEquals(0, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);
        $this->assertEquals(0, $batch->fresh()->jumlah);
        $this->assertEquals('dimusnahkan', $batch->fresh()->status);
    }

    public function test_proses_pemakaian_multi_batch(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batchA = $this->makeBatch($obat->id, $faskes->id, 60, 'BCH-A');
        $batchB = $this->makeBatch($obat->id, $faskes->id, 60, 'BCH-B');

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batchA->id, 'jumlah' => 60],
            ['obat_id' => $obat->id, 'batch_id' => $batchB->id, 'jumlah' => 40],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);

        $this->assertEquals(0, $batchA->fresh()->jumlah);
        $this->assertEquals('dimusnahkan', $batchA->fresh()->status);
        $this->assertEquals(20, $batchB->fresh()->jumlah);
        $this->assertEquals(20, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);
    }

    public function test_proses_pemakaian_mencatat_riwayat_stok(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);

        $pemakaian = $this->makePemakaian($faskes->id, 5, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 25],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);

        $riwayat = RiwayatStok::where('referensi_type', DetailPemakaianObat::class)
            ->where('referensi_id', $pemakaian->details->first()->id)
            ->first();

        $this->assertNotNull($riwayat);
        $this->assertEquals('keluar', $riwayat->tipe);
        $this->assertEquals(-25, $riwayat->jumlah);
        $this->assertEquals($faskes->id, $riwayat->fasilitas_id);
        $this->assertEquals($obat->id, $riwayat->obat_id);
        $this->assertEquals(5, $riwayat->user_id);
    }

    // ──────────────────────────────────────────────
    //  REVERSE PEMAKAIAN (stok dikembalikan)
    // ──────────────────────────────────────────────

    public function test_reverse_pemakaian_mengembalikan_stok_faskes(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 30],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);
        $this->assertEquals(70, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);

        $this->stokService->reversePemakaian($pemakaian->fresh('details'));

        $this->assertEquals(100, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);
        $this->assertEquals(100, $batch->fresh()->jumlah);
        $this->assertEquals('tersedia', $batch->fresh()->status);
    }

    public function test_reverse_pemakaian_mengaktifkan_kembali_batch_dimusnahkan(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 50);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 50],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);
        $this->assertEquals('dimusnahkan', $batch->fresh()->status);

        $this->stokService->reversePemakaian($pemakaian->fresh('details'));

        $this->assertEquals('tersedia', $batch->fresh()->status);
        $this->assertEquals(50, $batch->fresh()->jumlah);
    }

    public function test_reverse_pemakaian_mencatat_riwayat_masuk(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 20],
        ]);

        $this->stokService->prosesPemakaian($pemakaian);
        $this->stokService->reversePemakaian($pemakaian->fresh('details'));

        $riwayat = RiwayatStok::where('referensi_type', DetailPemakaianObat::class)
            ->where('referensi_id', $pemakaian->details->first()->id)
            ->where('tipe', 'masuk')
            ->first();

        $this->assertNotNull($riwayat);
        $this->assertEquals(20, $riwayat->jumlah);
    }

    // ──────────────────────────────────────────────
    //  VALIDASI BATCH
    // ──────────────────────────────────────────────

    public function test_proses_pemakaian_gagal_jika_batch_tidak_ditemukan(): void
    {
        $obat = $this->makeObat();
        $obatLain = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batchLain = $this->makeBatch($obatLain->id, $faskes->id, 100);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batchLain->id, 'jumlah' => 10],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->stokService->prosesPemakaian($pemakaian);
    }

    public function test_proses_pemakaian_gagal_jika_batch_status_bukan_tersedia(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);
        $batch->update(['status' => 'dimusnahkan']);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 10],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->stokService->prosesPemakaian($pemakaian);
    }

    public function test_proses_pemakaian_gagal_jika_batch_milik_faskes_lain(): void
    {
        $obat = $this->makeObat();
        $faskesA = $this->makeFaskes();
        $faskesB = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskesB->id, 100);

        $pemakaian = $this->makePemakaian($faskesA->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 10],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->stokService->prosesPemakaian($pemakaian);
    }

    public function test_proses_pemakaian_gagal_jika_stok_batch_tidak_cukup(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 5);

        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 10],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->stokService->prosesPemakaian($pemakaian);
    }

    // ──────────────────────────────────────────────
    //  ALUR EDIT (reverse lalu proses ulang)
    // ──────────────────────────────────────────────

    public function test_edit_pemakaian_mengembalikan_dan_menerapkan_stok_baru(): void
    {
        $obat = $this->makeObat();
        $faskes = $this->makeFaskes();
        $batch = $this->makeBatch($obat->id, $faskes->id, 100);

        // Pemakaian awal: 30 unit
        $pemakaian = $this->makePemakaian($faskes->id, 1, [
            ['obat_id' => $obat->id, 'batch_id' => $batch->id, 'jumlah' => 30],
        ]);
        $this->stokService->prosesPemakaian($pemakaian);
        $this->assertEquals(70, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);

        // Edit: ubah jadi 50 unit (reverse 30 dulu, lalu update detail ke 50, lalu proses 50)
        $this->stokService->reversePemakaian($pemakaian->fresh('details'));
        $this->assertEquals(100, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);

        $pemakaian->details->first()->update(['jumlah' => 50]);

        $this->stokService->prosesPemakaian($pemakaian->fresh('details'));
        $this->assertEquals(50, StokFaskes::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->first()->jumlah);
        $this->assertEquals(50, $batch->fresh()->jumlah);
    }
}
