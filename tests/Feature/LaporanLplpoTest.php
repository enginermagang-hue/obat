<?php

namespace Tests\Feature;

use App\Models\DetailLplpo;
use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\Obat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\User;
use App\Policies\LaporanLplpoPolicy;
use App\Services\LaporanLplpoService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanLplpoTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $userPuskesmas;

    private User $adminDinas;

    private FasilitasKesehatan $puskesmas;

    private FasilitasKesehatan $pustu;

    private Obat $obatA;

    private Obat $obatB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-LPLPO-001',
            'nama' => 'Puskesmas LPLPO Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-LPLPO-001',
            'nama' => 'Pustu LPLPO Test',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmas->id,
            'alamat' => 'Jl. Test 2',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->adminDinas = User::factory()->create(['name' => 'Admin Dinas']);
        $this->adminDinas->assignRole('admin_dinas');

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Puskesmas LPLPO',
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->obatA = Obat::create([
            'kode_obat' => 'OBT-LPLPO-A',
            'nama_obat' => 'Obat Test A',
            'kategori' => 'Obat Jadi',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'kemasan' => 'Box',
            'harga_satuan' => 5000,
            'ven_kategori' => 'V',
        ]);

        $this->obatB = Obat::create([
            'kode_obat' => 'OBT-LPLPO-B',
            'nama_obat' => 'Obat Test B',
            'kategori' => 'Obat Jadi',
            'satuan' => 'Kapsul',
            'bentuk_sediaan' => 'kapsul',
            'kemasan' => 'Box',
            'harga_satuan' => 3000,
            'ven_kategori' => 'E',
        ]);

        $this->seedRiwayatStok($this->puskesmas->id, $this->obatA->id, 100, 50, 30);
        $this->seedRiwayatStok($this->puskesmas->id, $this->obatB->id, 200, 100, 60);
    }

    public function test_puskesmas_user_can_create_lplpo(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-TEST-001',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $this->assertDatabaseHas('laporan_lplpo', [
            'nomor_laporan' => 'LPLPO-TEST-001',
            'fasilitas_id' => $this->puskesmas->id,
            'status' => 'draft',
        ]);
    }

    public function test_lplpo_service_generates_details_from_riwayat_stok(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-SERVICE-TEST',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        app(LaporanLplpoService::class)->generate($laporan);

        $details = DetailLplpo::where('lplpo_id', $laporan->id)->get();

        $this->assertGreaterThan(0, $details->count(), 'LPLPO harus memiliki detail');

        foreach ($details as $detail) {
            $this->assertNotNull($detail->obat_id);
            $this->assertNotNull($detail->stok_awal);
            $this->assertNotNull($detail->jumlah_masuk);
            $this->assertNotNull($detail->jumlah_keluar);
            $this->assertNotNull($detail->sisa_stok);
            $this->assertNotNull($detail->stok_optimum);
            $this->assertNotNull($detail->permintaan_selanjutnya);
            $this->assertGreaterThanOrEqual(0, $detail->sisa_stok);
            $this->assertGreaterThanOrEqual(0, $detail->stok_optimum);
            $this->assertGreaterThanOrEqual(0, $detail->permintaan_selanjutnya);
        }
    }

    public function test_lplpo_perhitungan_stok_optimum_dan_permintaan(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-CALC-TEST',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        app(LaporanLplpoService::class)->generate($laporan);

        $detailA = DetailLplpo::where('lplpo_id', $laporan->id)
            ->where('obat_id', $this->obatA->id)
            ->first();

        $this->assertNotNull($detailA);
        $this->assertEquals(120, $detailA->sisa_stok);
        $this->assertEquals(36, $detailA->stok_optimum);
        $this->assertEquals(0, $detailA->permintaan_selanjutnya);
    }

    public function test_lplpo_service_menghasilkan_detail_untuk_semua_obat_dengan_stok(): void
    {
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obatA->id,
            'jumlah' => 50,
            'batch_id' => null,
        ]);

        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-ALLITEMS',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        app(LaporanLplpoService::class)->generate($laporan);

        $obatIds = DetailLplpo::where('lplpo_id', $laporan->id)
            ->pluck('obat_id')
            ->toArray();

        $this->assertContains($this->obatA->id, $obatIds);
        $this->assertContains($this->obatB->id, $obatIds);
    }

    public function test_lplpo_dapat_disetujui(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-SELESAI',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->update(['status' => 'selesai']);

        $this->assertEquals('selesai', $laporan->fresh()->status);
    }

    public function test_create_lplpo_mengisi_stok_optimum_dan_satuan(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-FULL',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        app(LaporanLplpoService::class)->generate($laporan);

        $detailA = DetailLplpo::where('lplpo_id', $laporan->id)
            ->where('obat_id', $this->obatA->id)
            ->first();

        $this->assertNotNull($detailA->stok_optimum);
        $this->assertEquals('tablet', $detailA->obat->bentuk_sediaan);
    }

    public function test_lplpo_revisi_can_share_period_with_original(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-UNIQUE-1',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        // Revisi with same fasilitas+period should succeed (parent_lplpo_id != NULL)
        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-UNIQUE-2',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        $this->assertNotNull($revisi->id);
        $this->assertEquals($original->id, $revisi->parent_lplpo_id);
    }

    public function test_pustu_dapat_membuat_lplpo(): void
    {
        $userPustu = User::factory()->create([
            'name' => 'User Pustu LPLPO',
            'fasilitas_kesehatan_id' => $this->pustu->id,
        ]);
        $userPustu->assignRole('pustu');

        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-PUSTU-1',
            'fasilitas_id' => $this->pustu->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $userPustu->id,
        ]);

        $this->assertEquals($this->pustu->id, $laporan->fasilitas_id);
    }

    public function test_pdf_route_exists(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-PDF-TEST',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $route = route('admin.lplpo.cetak-pdf', ['lplpo' => $laporan->id]);

        $this->assertStringContainsString('admin/lplpo/', $route);
        $this->assertStringContainsString('cetak-pdf', $route);
    }

    public function test_policy_lplpo_create_hanya_user_faskes(): void
    {
        $this->assertFalse(
            (new LaporanLplpoPolicy)->create($this->superAdmin),
            'super_admin tidak boleh membuat LPLPO faskes'
        );

        $this->assertFalse(
            $this->adminDinas->hasPermissionTo('create_laporan_lplpo'),
            'admin_dinas tidak punya permission create_laporan_lplpo'
        );
        $this->assertFalse(
            (new LaporanLplpoPolicy)->create($this->adminDinas),
            'admin_dinas tidak bisa create LPLPO'
        );

        $this->assertTrue($this->userPuskesmas->can('create_laporan_lplpo'));
    }

    public function test_policy_admin_tidak_bisa_update_lplpo(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-POLICY-UPDATE',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $policy = new LaporanLplpoPolicy;

        $this->assertFalse($policy->update($this->superAdmin, $laporan), 'super_admin tidak boleh mengedit LPLPO');
        $this->assertFalse($policy->update($this->adminDinas, $laporan), 'admin_dinas tidak boleh mengedit LPLPO');

        $adminGudang = User::factory()->create();
        $adminGudang->assignRole('admin_gudang');
        $this->assertFalse($policy->update($adminGudang, $laporan), 'admin_gudang tidak boleh mengedit LPLPO');
    }

    public function test_policy_puskesmas_tidak_bisa_edit_lplpo_pustu(): void
    {
        $userPustu = User::factory()->create([
            'fasilitas_kesehatan_id' => $this->pustu->id,
        ]);
        $userPustu->assignRole('pustu');

        $lplpoPustu = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-PUSTU-EDIT',
            'fasilitas_id' => $this->pustu->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $userPustu->id,
        ]);

        $policy = new LaporanLplpoPolicy;

        $this->assertFalse(
            $policy->update($this->userPuskesmas, $lplpoPustu),
            'Puskesmas tidak boleh mengedit LPLPO dari Pustu-nya'
        );
    }

    private function seedRiwayatStok(int $fasilitasId, int $obatId, int $stokAwal, int $masuk, int $keluar): void
    {
        $userId = $this->userPuskesmas->id;

        RiwayatStok::create([
            'fasilitas_id' => $fasilitasId,
            'obat_id' => $obatId,
            'user_id' => $userId,
            'tipe' => 'masuk',
            'jumlah' => $stokAwal,
            'stok_sebelum' => 0,
            'stok_sesudah' => $stokAwal,
            'tanggal' => '2026-01-01',
            'keterangan' => 'Stok awal',
        ]);

        RiwayatStok::create([
            'fasilitas_id' => $fasilitasId,
            'obat_id' => $obatId,
            'user_id' => $userId,
            'tipe' => 'masuk',
            'jumlah' => $masuk,
            'stok_sebelum' => $stokAwal,
            'stok_sesudah' => $stokAwal + $masuk,
            'tanggal' => '2026-06-05',
            'keterangan' => 'Penerimaan',
        ]);

        $stokSetelahMasuk = $stokAwal + $masuk;
        RiwayatStok::create([
            'fasilitas_id' => $fasilitasId,
            'obat_id' => $obatId,
            'user_id' => $userId,
            'tipe' => 'keluar',
            'jumlah' => $keluar,
            'stok_sebelum' => $stokSetelahMasuk,
            'stok_sesudah' => $stokSetelahMasuk - $keluar,
            'tanggal' => '2026-06-15',
            'keterangan' => 'Pemakaian',
        ]);
    }
}
