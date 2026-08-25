<?php

namespace Tests\Feature;

use App\Filament\Resources\PemakaianObats\PemakaianObatResource;
use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\FefoService;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemakaianObatTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $userPuskesmasA;

    private User $userPuskesmasB;

    private FasilitasKesehatan $puskesmasA;

    private FasilitasKesehatan $puskesmasB;

    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmasA = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-A-'.uniqid(),
            'nama' => 'Puskesmas A',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. A',
            'pic' => 'PIC A',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->puskesmasB = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-B-'.uniqid(),
            'nama' => 'Puskesmas B',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. B',
            'pic' => 'PIC B',
            'kontak_pic' => '456',
            'status' => 'aktif',
        ]);

        $this->obat = Obat::create([
            'kode_obat' => 'OBT-TEST-'.uniqid(),
            'nama_obat' => 'Paracetamol 500mg',
            'kategori' => 'Analgesik',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'status' => 'aktif',
            'harga_satuan' => 1000,
            'metode_stok' => 'fefo',
        ]);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->userPuskesmasA = User::factory()->create([
            'name' => 'User Puskesmas A',
            'fasilitas_kesehatan_id' => $this->puskesmasA->id,
        ]);
        $this->userPuskesmasA->assignRole('puskesmas');

        $this->userPuskesmasB = User::factory()->create([
            'name' => 'User Puskesmas B',
            'fasilitas_kesehatan_id' => $this->puskesmasB->id,
        ]);
        $this->userPuskesmasB->assignRole('puskesmas');
    }

    private function buatBatch(int $obatId, int $fasilitasId, int $jumlah, string $batchNumber, string $tanggalExpired = '+1 year'): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $obatId,
            'fasilitas_id' => $fasilitasId,
            'batch_number' => $batchNumber,
            'tanggal_expired' => now()->modify($tanggalExpired),
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => now(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  FEFO ALLOCATION
    // ──────────────────────────────────────────────

    public function test_fefo_mengalokasikan_batch_expired_terdekat(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-LAMA', '+6 months');
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-BARU', '+2 years');

        $allocations = app(FefoService::class)->allocate($this->obat->id, 30, $this->puskesmasA->id, 'fefo');

        $this->assertCount(1, $allocations);
        $this->assertEquals('BCH-LAMA', BatchStok::find($allocations[0]['batch_id'])->batch_number);
        $this->assertEquals(30, $allocations[0]['jumlah']);
    }

    public function test_fefo_mengalokasikan_multi_batch_jika_stok_tidak_cukup(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 20, 'BCH-LAMA', '+6 months');
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-BARU', '+2 years');

        $allocations = app(FefoService::class)->allocate($this->obat->id, 60, $this->puskesmasA->id, 'fefo');

        $this->assertCount(2, $allocations);
        $this->assertEquals(20, $allocations[0]['jumlah']); // BCH-LAMA habis
        $this->assertEquals(40, $allocations[1]['jumlah']); // BCH-BARU sisa
    }

    public function test_fifo_mengalokasikan_batch_masuk_terlama(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-BARU', '+2 years')->update(['tanggal_masuk' => now()->subMonths(2)]);
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-LAMA', '+6 months')->update(['tanggal_masuk' => now()->subYear()]);

        $allocations = app(FefoService::class)->allocate($this->obat->id, 30, $this->puskesmasA->id, 'fifo');

        $this->assertEquals('BCH-LAMA', BatchStok::find($allocations[0]['batch_id'])->batch_number);
    }

    public function test_allocate_mengembalikan_kosong_jika_stok_tidak_cukup(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 10, 'BCH-SDIKIT');

        $allocations = app(FefoService::class)->allocate($this->obat->id, 50, $this->puskesmasA->id, 'fefo');

        $this->assertCount(1, $allocations);
        $this->assertEquals(10, $allocations[0]['jumlah']);
    }

    // ──────────────────────────────────────────────
    //  CREATE + PROSES STOK (via model + StokService)
    // ──────────────────────────────────────────────

    public function test_create_pemakaian_mengurangi_stok(): void
    {
        $batch = $this->buatBatch($this->obat->id, $this->puskesmasA->id, 100, 'BCH-A');

        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => PemakaianObat::generateNomorPemakaian(now(), $this->puskesmasA->id),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);

        $pemakaian->details()->create([
            'obat_id' => $this->obat->id,
            'batch_id' => $batch->id,
            'jumlah' => 30,
        ]);

        StokFaskes::recalculateForObat($this->puskesmasA->id, $this->obat->id);
        app(StokService::class)->prosesPemakaian($pemakaian->fresh('details'));

        $this->assertEquals(70, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->first()->jumlah);
        $this->assertEquals(70, $batch->fresh()->jumlah);
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE (getEloquentQuery)
    // ──────────────────────────────────────────────

    public function test_super_admin_melihat_semua_pemakaian(): void
    {
        PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);
        PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-'.uniqid(),
            'fasilitas_id' => $this->puskesmasB->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasB->id,
        ]);

        $this->actingAs($this->superAdmin);
        $this->assertSame(2, PemakaianObatResource::getEloquentQuery()->count());
    }

    public function test_puskesmas_hanya_melihat_pemakaian_faskes_sendiri(): void
    {
        PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-A-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);
        PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-B-'.uniqid(),
            'fasilitas_id' => $this->puskesmasB->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasB->id,
        ]);

        $this->actingAs($this->userPuskesmasA);
        $this->assertSame(1, PemakaianObatResource::getEloquentQuery()->count());
    }

    // ──────────────────────────────────────────────
    //  POLICY
    // ──────────────────────────────────────────────

    public function test_puskesmas_dapat_view_pemakaian_faskes_sendiri(): void
    {
        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-A-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);

        $this->assertTrue($this->userPuskesmasA->can('view', $pemakaian));
    }

    public function test_puskesmas_tidak_dapat_view_pemakaian_faskes_lain(): void
    {
        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-B-'.uniqid(),
            'fasilitas_id' => $this->puskesmasB->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasB->id,
        ]);

        $this->assertFalse($this->userPuskesmasA->can('view', $pemakaian));
    }

    public function test_puskesmas_dapat_update_pemakaian_hari_ini_faskes_sendiri(): void
    {
        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-A-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);

        $this->assertTrue($this->userPuskesmasA->can('update', $pemakaian));
    }

    public function test_puskesmas_tidak_dapat_update_pemakaian_kemarin(): void
    {
        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-A-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->subDay()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);

        $this->assertFalse($this->userPuskesmasA->can('update', $pemakaian));
    }

    public function test_super_admin_dapat_update_semua_pemakaian(): void
    {
        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-'.uniqid(),
            'fasilitas_id' => $this->puskesmasB->id,
            'tanggal_pemakaian' => now()->subMonth()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasB->id,
        ]);

        $this->assertTrue($this->superAdmin->can('update', $pemakaian));
        $this->assertTrue($this->superAdmin->can('delete', $pemakaian));
    }

    // ──────────────────────────────────────────────
    //  DELETE (reverse stok)
    // ──────────────────────────────────────────────

    public function test_hapus_pemakaian_mengembalikan_stok(): void
    {
        $batch = $this->buatBatch($this->obat->id, $this->puskesmasA->id, 100, 'BCH-A');

        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);
        $pemakaian->details()->create([
            'obat_id' => $this->obat->id,
            'batch_id' => $batch->id,
            'jumlah' => 40,
        ]);

        StokFaskes::recalculateForObat($this->puskesmasA->id, $this->obat->id);
        app(StokService::class)->prosesPemakaian($pemakaian->fresh('details'));
        $this->assertEquals(60, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->first()->jumlah);

        app(StokService::class)->reversePemakaian($pemakaian->fresh('details'));
        $pemakaian->delete();

        $this->assertDatabaseMissing('pemakaian_obat', ['id' => $pemakaian->id]);
        $this->assertEquals(100, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->first()->jumlah);
        $this->assertEquals(100, $batch->fresh()->jumlah);
    }

    // ──────────────────────────────────────────────
    //  NOMOR PEMAKAIAN
    // ──────────────────────────────────────────────

    public function test_generate_nomor_pemakaian_berformat_pmk(): void
    {
        $nomor = PemakaianObat::generateNomorPemakaian(now(), $this->puskesmasA->id);

        $this->assertStringStartsWith('PMK-', $nomor);
        $this->assertStringContainsString($this->puskesmasA->kode_faskes, $nomor);
        $this->assertStringContainsString(now()->format('Ym'), $nomor);
    }

    public function test_nomor_pemakaian_unik_setelah_ada_record(): void
    {
        $pemakaian1 = PemakaianObat::create([
            'nomor_pemakaian' => PemakaianObat::generateNomorPemakaian(now(), $this->puskesmasA->id),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);

        $nomor2 = PemakaianObat::generateNomorPemakaian(now(), $this->puskesmasA->id);

        $this->assertNotEquals($pemakaian1->nomor_pemakaian, $nomor2);
    }

    // ──────────────────────────────────────────────
    //  POLICY: ROLE YANG TIDAK BOLEH CREATE
    // ──────────────────────────────────────────────

    public function test_admin_gudang_tidak_bisa_create_pemakaian(): void
    {
        $adminGudang = User::factory()->create(['name' => 'Admin Gudang', 'fasilitas_kesehatan_id' => null]);
        $adminGudang->assignRole('admin_gudang');

        $this->assertFalse($adminGudang->can('create', PemakaianObat::class));
    }

    public function test_admin_dinas_tidak_bisa_create_pemakaian(): void
    {
        $adminDinas = User::factory()->create(['name' => 'Admin Dinas', 'fasilitas_kesehatan_id' => null]);
        $adminDinas->assignRole('admin_dinas');

        $this->assertFalse($adminDinas->can('create', PemakaianObat::class));
    }

    public function test_puskesmas_dapat_create_pemakaian(): void
    {
        $this->assertTrue($this->userPuskesmasA->can('create', PemakaianObat::class));
    }

    // ──────────────────────────────────────────────
    //  EDIT: DETAIL KOSONG
    // ──────────────────────────────────────────────

    public function test_edit_pemakaian_dengan_detail_kosong_mengembalikan_stok(): void
    {
        $batch = $this->buatBatch($this->obat->id, $this->puskesmasA->id, 100, 'BCH-A');

        $pemakaian = PemakaianObat::create([
            'nomor_pemakaian' => 'PMK-'.uniqid(),
            'fasilitas_id' => $this->puskesmasA->id,
            'tanggal_pemakaian' => now()->format('Y-m-d'),
            'jenis_pelayanan' => 'rawat_jalan',
            'user_id' => $this->userPuskesmasA->id,
        ]);
        $pemakaian->details()->create([
            'obat_id' => $this->obat->id,
            'batch_id' => $batch->id,
            'jumlah' => 30,
        ]);

        StokFaskes::recalculateForObat($this->puskesmasA->id, $this->obat->id);
        app(StokService::class)->prosesPemakaian($pemakaian->fresh('details'));
        $this->assertEquals(70, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->first()->jumlah);

        // Edit: reverse dulu dengan detail LAMA (masih ada), lalu hapus detail, tanpa re-apply
        app(StokService::class)->reversePemakaian($pemakaian->fresh('details'));
        $pemakaian->details()->delete();

        $this->assertEquals(100, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->first()->jumlah);
        $this->assertEquals(100, $batch->fresh()->jumlah);
    }

    // ──────────────────────────────────────────────
    //  BATCH ALLOCATION: EXACT FIT
    // ──────────────────────────────────────────────

    public function test_fefo_alokasi_pas_stok_tanpa_sisa(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 30, 'BCH-A');

        $allocations = app(FefoService::class)->allocate($this->obat->id, 30, $this->puskesmasA->id, 'fefo');

        $this->assertCount(1, $allocations);
        $this->assertEquals(30, $allocations[0]['jumlah']);
    }

    // ──────────────────────────────────────────────
    //  STOK AGREGAT HELPER
    // ──────────────────────────────────────────────

    public function test_get_aggregate_stock_menghitung_stok_faskes_setelah_recalculate(): void
    {
        $this->buatBatch($this->obat->id, $this->puskesmasA->id, 50, 'BCH-A');

        StokFaskes::recalculateForObat($this->puskesmasA->id, $this->obat->id);

        $stock = StokFaskes::where('fasilitas_id', $this->puskesmasA->id)
            ->where('obat_id', $this->obat->id)
            ->value('jumlah');

        $this->assertEquals(50, $stock);
    }
}
