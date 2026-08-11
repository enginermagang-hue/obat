<?php

namespace Tests\Feature;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\Obat;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\LaporanLplpoService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanLplpoValidasiTest extends TestCase
{
    use RefreshDatabase;

    private User $userPuskesmas;

    private FasilitasKesehatan $puskesmas;

    private Obat $obatA;

    private LaporanLplpoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->service = app(LaporanLplpoService::class);

        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-VAL-001',
            'nama' => 'Puskesmas Validasi Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Validasi',
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->obatA = Obat::create([
            'kode_obat' => 'OBT-VAL-A',
            'nama_obat' => 'Obat Validasi A',
            'kategori' => 'Obat Jadi',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'kemasan' => 'Box',
            'harga_satuan' => 5000,
            'ven_kategori' => 'V',
        ]);
    }

    public function test_validate_returns_empty_when_data_consistent(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-001',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,  // 100 + 50 - 30 = 120 ✓
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        $result = $this->service->validate($laporan);

        $this->assertEmpty($result['errors']);
    }

    public function test_validate_detects_inconsistent_sisa_stok(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-002',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 999,  // WRONG: should be 120
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        $result = $this->service->validate($laporan);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Sisa stok', $result['errors'][0]);
    }

    public function test_validate_warns_on_stok_selisih(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-003',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        // StokFaskes has different value
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obatA->id,
            'jumlah' => 200,  // DIFFERENT from LPLPO's 120
        ]);

        $result = $this->service->validate($laporan);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('selisih', strtolower($result['warnings'][0]));
    }

    public function test_validate_no_warning_when_stok_matches(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-004',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obatA->id,
            'jumlah' => 120,  // MATCHES LPLPO
        ]);

        $result = $this->service->validate($laporan);

        $this->assertEmpty($result['warnings']);
    }

    public function test_stok_faskes_comparison_returns_correct_data(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-005',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obatA->id,
            'jumlah' => 100,
        ]);

        $comparison = $this->service->getStokFaskesComparison($laporan);

        $this->assertCount(1, $comparison);
        $this->assertEquals(120, $comparison[0]['stok_lplpo']);
        $this->assertEquals(100, $comparison[0]['stok_sistem']);
        $this->assertEquals(-20, $comparison[0]['selisih']);
        $this->assertContains($comparison[0]['status'], ['sesuai', 'minor', 'moderate', 'signifikan']);
    }

    public function test_stok_faskes_comparison_handles_no_stok_faskes(): void
    {
        $laporan = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-VAL-006',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $laporan->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        // No StokFaskes record
        $comparison = $this->service->getStokFaskesComparison($laporan);

        $this->assertCount(1, $comparison);
        $this->assertEquals(0, $comparison[0]['stok_sistem']);
        $this->assertEquals(120, $comparison[0]['stok_lplpo']);
        $this->assertEquals(-120, $comparison[0]['selisih']);
    }
}
