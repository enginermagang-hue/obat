<?php

namespace Tests\Feature;

use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenerimaanStokNonDistribusiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private FasilitasKesehatan $puskesmas;

    private Obat $obat;

    private string $batchNumber;

    private string $tanggalExpired;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-TEST',
            'nama' => 'Puskesmas Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->obat = Obat::factory()->create();
        $this->batchNumber = 'BCH-'.uniqid();
        $this->tanggalExpired = now()->addYear()->toDateString();
    }

    private function makePenerimaan(
        ?int $fasilitasId,
        string $tipe = 'pembelian',
        string $status = 'draft',
        int $jumlah = 50,
    ): PenerimaanStok {
        $penerimaan = PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'tipe' => $tipe,
            'fasilitas_id' => $fasilitasId,
            'user_id' => $this->superAdmin->id,
            'status' => $status,
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => $jumlah,
            'harga_satuan' => 1000,
            'sub_total' => $jumlah * 1000,
        ]);

        return $penerimaan->fresh();
    }

    public function test_proses_penerimaan_adds_to_stok_faskes_when_fasilitas_id_present(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 50);

        $this->assertDatabaseMissing('stok_faskes', [
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
        ]);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $stokFaskes = StokFaskes::where('fasilitas_id', $this->puskesmas->id)
            ->where('obat_id', $this->obat->id)
            ->first();

        $this->assertNotNull($stokFaskes);
        $this->assertSame(50, (int) $stokFaskes->jumlah);
    }

    public function test_proses_penerimaan_does_not_add_to_stok_gudang_when_fasilitas_id_present(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 50);
        $stokGudangBefore = StokGudang::where('obat_id', $this->obat->id)->first();

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $stokGudangAfter = StokGudang::where('obat_id', $this->obat->id)->first();

        $this->assertNull($stokGudangBefore);
        $this->assertNull($stokGudangAfter);
    }

    public function test_proses_penerimaan_adds_to_stok_gudang_when_fasilitas_id_null(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: null, tipe: 'pembelian', jumlah: 100);

        $this->assertDatabaseMissing('stok_gudang', [
            'obat_id' => $this->obat->id,
        ]);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $stokGudang = StokGudang::where('obat_id', $this->obat->id)->first();

        $this->assertNotNull($stokGudang);
        $this->assertSame(100, (int) $stokGudang->jumlah);
    }

    public function test_proses_penerimaan_creates_batch_stok_for_faskes(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 30);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $this->assertDatabaseHas('batch_stok', [
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'fasilitas_id' => $this->puskesmas->id,
            'jumlah' => 30,
            'status' => 'tersedia',
        ]);
    }

    public function test_proses_penerimaan_creates_batch_stok_for_gudang(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: null, tipe: 'pembelian', jumlah: 30);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $this->assertDatabaseHas('batch_stok', [
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'fasilitas_id' => null,
            'jumlah' => 30,
            'status' => 'tersedia',
        ]);
    }

    public function test_proses_penerimaan_increments_existing_batch_stok(): void
    {
        BatchStok::create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'fasilitas_id' => $this->puskesmas->id,
            'jumlah' => 20,
            'status' => 'tersedia',
            'tanggal_masuk' => now()->toDateString(),
        ]);

        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 30);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $batch = BatchStok::where('obat_id', $this->obat->id)
            ->where('batch_number', $this->batchNumber)
            ->where('fasilitas_id', $this->puskesmas->id)
            ->first();

        $this->assertSame(50, (int) $batch->jumlah);
    }

    public function test_proses_penerimaan_creates_riwayat_stok(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 25);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $this->assertDatabaseHas('riwayat_stok', [
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
            'tipe' => 'masuk',
            'jumlah' => 25,
            'stok_sebelum' => 0,
            'stok_sesudah' => 25,
        ]);
    }

    public function test_proses_penerimaan_multiple_times_accumulates_stok(): void
    {
        $p1 = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 30);
        $p2 = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'hibah', jumlah: 20);

        app(StokService::class)->prosesPenerimaan($p1);
        app(StokService::class)->prosesPenerimaan($p2);

        $stokFaskes = StokFaskes::where('fasilitas_id', $this->puskesmas->id)
            ->where('obat_id', $this->obat->id)
            ->first();

        $this->assertSame(50, (int) $stokFaskes->jumlah);
        $this->assertSame(2, RiwayatStok::where('obat_id', $this->obat->id)->count());
    }

    public function test_reverse_penerimaan_restores_stok_faskes(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'pembelian', jumlah: 50);
        app(StokService::class)->prosesPenerimaan($penerimaan);

        $previousDetails = $penerimaan->details()->get();
        app(StokService::class)->reversePenerimaan($penerimaan, $previousDetails);

        $stokFaskes = StokFaskes::where('fasilitas_id', $this->puskesmas->id)
            ->where('obat_id', $this->obat->id)
            ->first();

        $this->assertSame(0, (int) $stokFaskes->jumlah);
    }

    public function test_proses_penerimaan_handles_manual_tipe_with_fasilitas(): void
    {
        $penerimaan = $this->makePenerimaan(fasilitasId: $this->puskesmas->id, tipe: 'manual', jumlah: 10);

        app(StokService::class)->prosesPenerimaan($penerimaan);

        $stokFaskes = StokFaskes::where('fasilitas_id', $this->puskesmas->id)
            ->where('obat_id', $this->obat->id)
            ->first();

        $this->assertNotNull($stokFaskes);
        $this->assertSame(10, (int) $stokFaskes->jumlah);
    }
}
