<?php

namespace Tests\Feature;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanRko;
use App\Models\Obat;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CetakRkoTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private FasilitasKesehatan $puskesmasA;

    private FasilitasKesehatan $puskesmasB;

    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmasA = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-RKO-A',
            'nama' => 'Puskesmas RKO Test A',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test A',
            'pic' => 'PIC A',
            'kontak_pic' => '111',
            'status' => 'aktif',
        ]);

        $this->puskesmasB = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-RKO-B',
            'nama' => 'Puskesmas RKO Test B',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test B',
            'pic' => 'PIC B',
            'kontak_pic' => '222',
            'status' => 'aktif',
        ]);

        $this->userA = User::factory()->create([
            'name' => 'User Puskesmas A',
            'fasilitas_kesehatan_id' => $this->puskesmasA->id,
        ]);
        $this->userA->assignRole('puskesmas');

        $this->userB = User::factory()->create([
            'name' => 'User Puskesmas B',
            'fasilitas_kesehatan_id' => $this->puskesmasB->id,
        ]);
        $this->userB->assignRole('puskesmas');

        $this->obat = Obat::create([
            'kode_obat' => 'OBT-RKO-001',
            'nama_obat' => 'Obat RKO Test',
            'kategori' => 'Obat Jadi',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'status' => 'aktif',
            'harga_satuan' => 1500,
        ]);
    }

    private function buatRko(string $status): LaporanRko
    {
        $rko = LaporanRko::create([
            'nomor_rko' => 'RKO-CETAK-'.$status,
            'fasilitas_id' => $this->puskesmasA->id,
            'periode_tahun' => 2026,
            'status' => $status,
            'tanggal_pembuatan' => now(),
            'tanggal_pengajuan' => now(),
            'total_anggaran' => 30000,
            'dibuat_oleh' => $this->userA->id,
        ]);

        $rko->details()->create([
            'obat_id' => $this->obat->id,
            'pemakaian_tahun_sebelumnya' => 120,
            'rata_rata_pemakaian_bulanan' => 10,
            'stok_akhir' => 30,
            'kebutuhan_tahunan' => 180,
            'rencana_kebutuhan' => 150,
            'usulan' => 180,
            'buffer_stock_persen' => 30,
            'buffer_stok_qty' => 45,
            'total_kebutuhan' => 195,
            'ven_kategori' => 'V',
            'harga_perkiraan' => 1500,
            'total_harga' => 270000,
        ]);

        return $rko;
    }

    public function test_cetak_pdf_gagal_jika_status_bukan_disetujui(): void
    {
        $rko = $this->buatRko('draft');

        $this->actingAs($this->userA)
            ->get(route('admin.rko.cetak-pdf', ['rko' => $rko->id]))
            ->assertStatus(403);
    }

    public function test_cetak_pdf_berhasil_saat_disetujui(): void
    {
        $rko = $this->buatRko('disetujui');

        $response = $this->actingAs($this->userA)
            ->get(route('admin.rko.cetak-pdf', ['rko' => $rko->id]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_cetak_xls_berhasil_dengan_kolom_standar_rko(): void
    {
        $rko = $this->buatRko('disetujui');

        $response = $this->actingAs($this->userA)
            ->get(route('admin.rko.cetak-xls', ['rko' => $rko->id]));

        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Rencana Keb.', $content);
        $this->assertStringContainsString('Total Keb.', $content);
        $this->assertStringContainsString('Obat RKO Test', $content);
        $this->assertStringContainsString('Total Anggaran', $content);
        $this->assertStringContainsString('270000', $content);
    }

    public function test_cetak_xls_gagal_jika_belum_disetujui(): void
    {
        $rko = $this->buatRko('diajukan');

        $this->actingAs($this->userA)
            ->get(route('admin.rko.cetak-xls', ['rko' => $rko->id]))
            ->assertStatus(403);
    }

    public function test_cetak_ditolak_untuk_faskes_lain(): void
    {
        $rko = $this->buatRko('disetujui');

        $this->actingAs($this->userB)
            ->get(route('admin.rko.cetak-xls', ['rko' => $rko->id]))
            ->assertStatus(403);
    }
}
