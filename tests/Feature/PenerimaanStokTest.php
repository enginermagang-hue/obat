<?php

namespace Tests\Feature;

use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Models\BatchStok;
use App\Models\DetailPenerimaanStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\SumberDana;
use App\Models\User;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenerimaanStokTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminGudang;

    private User $adminDinas;

    private User $userPuskesmas;

    private User $userPustu;

    private FasilitasKesehatan $puskesmasA;

    private FasilitasKesehatan $puskesmasB;

    private FasilitasKesehatan $pustu;

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

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-'.uniqid(),
            'nama' => 'Pustu',
            'tipe' => 'pustu',
            'alamat' => 'Jl. Pustu',
            'pic' => 'PIC Pustu',
            'kontak_pic' => '789',
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

        $this->adminGudang = User::factory()->create(['name' => 'Admin Gudang', 'fasilitas_kesehatan_id' => null]);
        $this->adminGudang->assignRole('admin_gudang');

        $this->adminDinas = User::factory()->create(['name' => 'Admin Dinas', 'fasilitas_kesehatan_id' => null]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Puskesmas A',
            'fasilitas_kesehatan_id' => $this->puskesmasA->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->userPustu = User::factory()->create([
            'name' => 'User Pustu',
            'fasilitas_kesehatan_id' => $this->pustu->id,
        ]);
        $this->userPustu->assignRole('pustu');
    }

    private function buatPenerimaan(array $overrides = []): PenerimaanStok
    {
        return PenerimaanStok::create(array_merge([
            'nomor_penerimaan' => PenerimaanStok::generateNomorPenerimaan(null, now()->format('Y-m-d')),
            'tipe' => 'pembelian',
            'status' => 'draft',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'fasilitas_id' => null,
            'user_id' => $this->superAdmin->id,
            'total_biaya' => 0,
        ], $overrides));
    }

    private function buatDetail(PenerimaanStok $penerimaan, array $overrides = []): DetailPenerimaanStok
    {
        return $penerimaan->details()->create(array_merge([
            'obat_id' => $this->obat->id,
            'batch_number' => 'BCH-'.uniqid(),
            'tanggal_expired' => now()->addYear(),
            'jumlah' => 100,
            'harga_satuan' => 1000,
            'sub_total' => 100000,
        ], $overrides));
    }

    // ──────────────────────────────────────────────
    //  NOMOR PENERIMAAN
    // ──────────────────────────────────────────────

    public function test_generate_nomor_penerimaan_berformat_po(): void
    {
        $nomor = PenerimaanStok::generateNomorPenerimaan(null, now()->format('Y-m-d'));

        $this->assertStringContainsString('/', $nomor);
        $this->assertStringContainsString(now()->format('Y'), $nomor);
    }

    public function test_nomor_penerimaan_auto_generate_saat_creating(): void
    {
        $penerimaan = PenerimaanStok::create([
            'tipe' => 'pembelian',
            'status' => 'draft',
            'tanggal_penerimaan' => now()->format('Y-m-d'),
            'fasilitas_id' => null,
            'user_id' => $this->superAdmin->id,
            'total_biaya' => 0,
        ]);

        $this->assertNotNull($penerimaan->nomor_penerimaan);
        $this->assertNotEmpty($penerimaan->nomor_penerimaan);
    }

    // ──────────────────────────────────────────────
    //  PROSES PENERIMAAN (stok bertambah)
    // ──────────────────────────────────────────────

    public function test_proses_penerimaan_stok_awal_meningkatkan_stok_gudang(): void
    {
        $penerimaan = $this->buatPenerimaan(['tipe' => 'stok_awal']);
        $this->buatDetail($penerimaan, ['jumlah' => 50]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        $this->assertEquals(50, StokGudang::where('obat_id', $this->obat->id)->value('jumlah'));
    }

    public function test_proses_penerimaan_pembelian_meningkatkan_stok_faskes(): void
    {
        $penerimaan = $this->buatPenerimaan([
            'tipe' => 'pembelian',
            'fasilitas_id' => $this->puskesmasA->id,
        ]);
        $this->buatDetail($penerimaan, ['jumlah' => 80]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        $this->assertEquals(80, StokFaskes::where('fasilitas_id', $this->puskesmasA->id)->where('obat_id', $this->obat->id)->value('jumlah'));
    }

    public function test_proses_penerimaan_membuat_batch_stok(): void
    {
        $penerimaan = $this->buatPenerimaan(['tipe' => 'pembelian']);
        $detail = $this->buatDetail($penerimaan, ['batch_number' => 'BCH-TEST-001', 'jumlah' => 60]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        $this->assertDatabaseHas('batch_stok', [
            'penerimaan_id' => $penerimaan->id,
            'obat_id' => $this->obat->id,
            'batch_number' => 'BCH-TEST-001',
            'jumlah' => 60,
            'status' => 'tersedia',
        ]);
    }

    public function test_proses_penerimaan_mencatat_riwayat_stok(): void
    {
        $penerimaan = $this->buatPenerimaan(['tipe' => 'pembelian']);
        $detail = $this->buatDetail($penerimaan, ['jumlah' => 25]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        $riwayat = RiwayatStok::where('referensi_type', PenerimaanStok::class)
            ->where('referensi_id', $penerimaan->id)
            ->first();

        $this->assertNotNull($riwayat);
        $this->assertEquals('masuk', $riwayat->tipe);
        $this->assertEquals(25, $riwayat->jumlah);
        $this->assertEquals($this->obat->id, $riwayat->obat_id);
    }

    public function test_proses_penerimaan_membuat_batch_stok_dengan_sumber_dana(): void
    {
        $sumberDana = SumberDana::create([
            'kode' => 'SD-'.uniqid(),
            'nama' => 'Sumber Dana Batch',
            'tahun' => now()->year,
            'total_anggaran' => 1000000,
        ]);

        $penerimaan = $this->buatPenerimaan([
            'tipe' => 'pembelian',
            'sumber_dana_id' => $sumberDana->id,
        ]);
        $detail = $this->buatDetail($penerimaan, ['batch_number' => 'BCH-SD-001', 'jumlah' => 30]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        $batch = BatchStok::where('batch_number', 'BCH-SD-001')->first();
        $this->assertNotNull($batch);
        $this->assertEquals($sumberDana->id, $batch->sumber_dana_id);
    }

    // ──────────────────────────────────────────────
    //  REVERSE PENERIMAAN (draft edit/hapus)
    // ──────────────────────────────────────────────

    public function test_reverse_penerimaan_kembalikan_stok_dan_menghapus_batch(): void
    {
        $penerimaan = $this->buatPenerimaan(['tipe' => 'pembelian']);
        $detail = $this->buatDetail($penerimaan, ['batch_number' => 'BCH-REV', 'jumlah' => 40]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));
        $this->assertEquals(40, StokGudang::where('obat_id', $this->obat->id)->value('jumlah'));

        app(StokService::class)->reversePenerimaan($penerimaan, collect([$detail]));

        $this->assertEquals(0, StokGudang::where('obat_id', $this->obat->id)->value('jumlah'));
        $this->assertDatabaseMissing('batch_stok', ['id' => $detail->batch_id ?? 0]);
    }

    public function test_reverse_penerimaan_menghapus_realisasi_sumber_dana(): void
    {
        $sumberDana = SumberDana::create([
            'kode' => 'SD-'.uniqid(),
            'nama' => 'Sumber Dana Reverse',
            'tahun' => now()->year,
            'total_anggaran' => 1000000,
        ]);

        $penerimaan = $this->buatPenerimaan([
            'tipe' => 'pembelian',
            'sumber_dana_id' => $sumberDana->id,
            'total_biaya' => 30000,
        ]);
        $detail = $this->buatDetail($penerimaan, ['jumlah' => 10]);

        app(StokService::class)->prosesPenerimaan($penerimaan->fresh('details'));

        app(StokService::class)->reversePenerimaan($penerimaan, collect([$detail]));

        $this->assertDatabaseMissing('sumber_dana_penggunaan', [
            'sumber_dana_id' => $sumberDana->id,
            'penerimaan_stok_id' => $penerimaan->id,
        ]);
    }

    // ──────────────────────────────────────────────
    //  TIPE DOKUMEN
    // ──────────────────────────────────────────────

    public function test_penerimaan_bisa_berbagai_tipe_dokumen(): void
    {
        $tipeList = ['pembelian', 'hibah', 'stok_awal', 'penyesuaian', 'manual'];

        foreach ($tipeList as $tipe) {
            $penerimaan = $this->buatPenerimaan(['tipe' => $tipe]);
            $this->assertEquals($tipe, $penerimaan->tipe);
        }
    }

    // ──────────────────────────────────────────────
    //  POLICY
    // ──────────────────────────────────────────────

    public function test_super_admin_bisa_view_semua_penerimaan(): void
    {
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasA->id]);
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasB->id]);

        $this->actingAs($this->superAdmin);
        $this->assertSame(2, PenerimaanStokResource::getEloquentQuery()->count());
    }

    public function test_puskesmas_hanya_melihat_penerimaan_faskes_sendiri(): void
    {
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasA->id]);
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasB->id]);

        $this->actingAs($this->userPuskesmas);
        $this->assertSame(1, PenerimaanStokResource::getEloquentQuery()->count());
    }

    public function test_admin_gudang_dapat_create_penerimaan(): void
    {
        $this->assertTrue($this->adminGudang->can('create', PenerimaanStok::class));
    }

    public function test_puskesmas_tidak_bisa_update_penerimaan_yang_sudah_dikonfirmasi(): void
    {
        $penerimaan = $this->buatPenerimaan([
            'fasilitas_id' => $this->puskesmasA->id,
            'status' => 'dikonfirmasi',
        ]);

        $this->assertFalse($this->userPuskesmas->can('update', $penerimaan));
        $this->assertFalse($this->userPuskesmas->can('delete', $penerimaan));
    }

    public function test_super_admin_bisa_update_draft(): void
    {
        $penerimaan = $this->buatPenerimaan(['status' => 'draft']);

        $this->assertTrue($this->superAdmin->can('update', $penerimaan));
        $this->assertTrue($this->superAdmin->can('delete', $penerimaan));
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE
    // ──────────────────────────────────────────────

    public function test_admin_dinas_melihat_semua_penerimaan(): void
    {
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasA->id]);
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasB->id]);

        $this->actingAs($this->adminDinas);
        $this->assertSame(2, PenerimaanStokResource::getEloquentQuery()->count());
    }

    public function test_admin_gudang_melihat_semua_penerimaan(): void
    {
        $this->buatPenerimaan(['fasilitas_id' => $this->puskesmasA->id]);
        $this->buatPenerimaan(['fasilitas_id' => $this->pustu->id]);

        $this->actingAs($this->adminGudang);
        $this->assertSame(2, PenerimaanStokResource::getEloquentQuery()->count());
    }
}
