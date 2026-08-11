<?php

namespace Tests\Feature;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\Obat;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanLplpoRevisiTest extends TestCase
{
    use RefreshDatabase;

    private User $userPuskesmas;

    private FasilitasKesehatan $puskesmas;

    private Obat $obatA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-REV-001',
            'nama' => 'Puskesmas Revisi Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Puskesmas Revisi',
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->obatA = Obat::create([
            'kode_obat' => 'OBT-REV-A',
            'nama_obat' => 'Obat Revisi A',
            'kategori' => 'Obat Jadi',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'kemasan' => 'Box',
            'harga_satuan' => 5000,
            'ven_kategori' => 'V',
        ]);
    }

    public function test_create_revisi_from_selesai_lplpo(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-001',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $original->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 0,
        ]);

        // Create revisi
        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-002',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        // Verify parent relationship
        $this->assertNotNull($revisi->parent_lplpo_id);
        $this->assertEquals($original->id, $revisi->parent_lplpo_id);
        $this->assertEquals($original->id, $revisi->parentLplpo->id);
    }

    public function test_revisi_copies_details_from_original(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-003',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $original->details()->create([
            'obat_id' => $this->obatA->id,
            'stok_awal' => 100,
            'jumlah_masuk' => 50,
            'jumlah_keluar' => 30,
            'sisa_stok' => 120,
            'stok_optimum' => 36,
            'permintaan_selanjutnya' => 5,
        ]);

        // Simulate revisi creation (copy details)
        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-004',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        foreach ($original->details as $detail) {
            $revisi->details()->create([
                'obat_id' => $detail->obat_id,
                'stok_awal' => $detail->stok_awal,
                'jumlah_masuk' => $detail->jumlah_masuk,
                'jumlah_keluar' => $detail->jumlah_keluar,
                'sisa_stok' => $detail->sisa_stok,
                'stok_optimum' => $detail->stok_optimum,
                'permintaan_selanjutnya' => $detail->permintaan_selanjutnya,
                'keterangan' => $detail->keterangan,
            ]);
        }

        $this->assertEquals(1, $revisi->details->count());
        $this->assertEquals($this->obatA->id, $revisi->details->first()->obat_id);
        $this->assertEquals(120, $revisi->details->first()->sisa_stok);
    }

    public function test_revisi_parent_lplpo_relationship(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-005',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-006',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        // Test parentLplpo relationship
        $this->assertNotNull($revisi->parentLplpo);
        $this->assertEquals($original->id, $revisi->parentLplpo->id);
        $this->assertEquals('LPLPO-REV-005', $revisi->parentLplpo->nomor_laporan);

        // Test revisiLplpo relationship
        $this->assertEquals(1, $original->revisiLplpo->count());
        $this->assertEquals($revisi->id, $original->revisiLplpo->first()->id);
    }

    public function test_only_current_lplpo_appear_in_default_query(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-007',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-008',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        // Default query (with parent_lplpo_id IS NULL filter from Resource)
        $defaultQuery = LaporanLplpo::query()->whereNull('parent_lplpo_id');
        $this->assertEquals(1, $defaultQuery->count());
        $this->assertTrue($defaultQuery->where('id', $original->id)->exists());
        $this->assertFalse($defaultQuery->where('id', $revisi->id)->exists());

        // Query without filter shows both
        $allQuery = LaporanLplpo::query();
        $this->assertEquals(2, $allQuery->count());
    }

    public function test_revisi_can_be_edited_while_draft(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-009',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-010',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        // Revisi should be editable (status draft)
        $this->assertEquals('draft', $revisi->status);

        // Original should be locked (status selesai)
        $this->assertEquals('selesai', $original->status);
    }

    public function test_revisi_status_can_become_selesai(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-011',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $revisi = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-012',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        $revisi->update(['status' => 'selesai']);

        $this->assertEquals('selesai', $revisi->fresh()->status);
    }

    public function test_multiple_revisions_chain(): void
    {
        $original = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-013',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
        ]);

        $revisi1 = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-014',
            'fasilitas_id' => $original->fasilitas_id,
            'periode_bulan' => $original->periode_bulan,
            'periode_tahun' => $original->periode_tahun,
            'status' => 'selesai',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $original->id,
        ]);

        $revisi2 = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-REV-015',
            'fasilitas_id' => $revisi1->fasilitas_id,
            'periode_bulan' => $revisi1->periode_bulan,
            'periode_tahun' => $revisi1->periode_tahun,
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $this->userPuskesmas->id,
            'parent_lplpo_id' => $revisi1->id,
        ]);

        // Original has 1 child (revisi1)
        $this->assertEquals(1, $original->revisiLplpo->count());
        $this->assertEquals($revisi1->id, $original->revisiLplpo->first()->id);

        // Revisi1 has 1 child (revisi2)
        $this->assertEquals(1, $revisi1->revisiLplpo->count());
        $this->assertEquals($revisi2->id, $revisi1->revisiLplpo->first()->id);

        // Revisi2 is the latest (no children)
        $this->assertEquals(0, $revisi2->revisiLplpo->count());
    }
}
