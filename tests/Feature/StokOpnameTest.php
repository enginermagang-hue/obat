<?php

namespace Tests\Feature;

use App\Filament\Resources\OpnameStoks\OpnameStokResource;
use App\Models\BatchStok;
use App\Models\DetailOpnameStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\OpnameStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokOpnameTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminGudang;

    private User $adminDinas;

    private User $puskesmasUser;

    private User $pustuUser;

    private FasilitasKesehatan $puskesmas;

    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmas = FasilitasKesehatan::factory()->create([
            'kode_faskes' => 'PKM-OPNAME',
            'nama' => 'Puskesmas Opname',
            'tipe' => 'puskesmas',
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->adminGudang = User::factory()->create(['fasilitas_kesehatan_id' => null]);
        $this->adminGudang->assignRole('admin_gudang');

        $this->adminDinas = User::factory()->create(['fasilitas_kesehatan_id' => null]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->puskesmasUser = User::factory()->create([
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->puskesmasUser->assignRole('puskesmas');

        $this->pustuUser = User::factory()->create();
        $this->pustuUser->assignRole('pustu');

        $this->obat = Obat::factory()->create([
            'kode_obat' => 'OBT-OPNAME',
            'nama_obat' => 'Obat Opname',
        ]);
    }

    private function makeOpname(string $tipe, string $status, ?int $fasilitasId, int $userId): OpnameStok
    {
        return OpnameStok::create([
            'tipe' => $tipe,
            'status' => $status,
            'fasilitas_id' => $fasilitasId,
            'tanggal_opname' => '2026-08-20',
            'user_id' => $userId,
        ]);
    }

    private function makeDetail(OpnameStok $opname, array $attributes = []): DetailOpnameStok
    {
        return $opname->details()->create(array_merge([
            'obat_id' => $this->obat->id,
            'stok_sistem' => 100,
            'stok_fisik' => 110,
            'selisih' => 10,
        ], $attributes));
    }

    private function makeBatch(?int $fasilitasId, int $jumlah, string $batchNumber = 'BCH-OPNAME'): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $fasilitasId,
            'batch_number' => $batchNumber,
            'tanggal_expired' => '2027-08-20',
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => '2026-08-01',
        ]);
    }

    public function test_nomor_opname_digenerate_dengan_prefix_tipe(): void
    {
        $penyesuaian = $this->makeOpname('penyesuaian', 'draft', $this->puskesmas->id, $this->puskesmasUser->id);
        $stokAwal = $this->makeOpname('stok_awal', 'draft', $this->puskesmas->id, $this->puskesmasUser->id);
        $stokBaru = $this->makeOpname('stok_baru', 'draft', $this->puskesmas->id, $this->puskesmasUser->id);

        $this->assertStringStartsWith('OPN/', $penyesuaian->nomor_opname);
        $this->assertStringStartsWith('STK-AWAL/', OpnameStok::generateNomorOpname($stokAwal, 'stok_awal'));
        $this->assertStringStartsWith('STK-BARU/', OpnameStok::generateNomorOpname($stokBaru, 'stok_baru'));
    }

    public function test_nomor_opname_eksplisit_dipertahankan(): void
    {
        $opname = OpnameStok::create([
            'nomor_opname' => 'OPN-CUSTOM-001',
            'tipe' => 'penyesuaian',
            'status' => 'draft',
            'fasilitas_id' => $this->puskesmas->id,
            'tanggal_opname' => '2026-08-20',
            'user_id' => $this->puskesmasUser->id,
        ]);

        $this->assertSame('OPN-CUSTOM-001', $opname->nomor_opname);
    }

    public function test_policy_mengikuti_permission_role(): void
    {
        $opname = $this->makeOpname('penyesuaian', 'draft', $this->puskesmas->id, $this->puskesmasUser->id);

        $this->assertTrue($this->superAdmin->can('create', OpnameStok::class));
        $this->assertTrue($this->adminGudang->can('create', OpnameStok::class));
        $this->assertFalse($this->adminDinas->can('create', OpnameStok::class));
        $this->assertTrue($this->puskesmasUser->can('delete', $opname));
        $this->assertFalse($this->pustuUser->can('viewAny', OpnameStok::class));
    }

    public function test_proses_opname_penyesuaian_menambah_stok_faskes_dan_batch(): void
    {
        $this->makeBatch($this->puskesmas->id, 100, 'BCH-LAMA');
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
            'jumlah' => 100,
            'stok_minimum' => 0,
        ]);
        $opname = $this->makeOpname('penyesuaian', 'selesai', $this->puskesmas->id, $this->puskesmasUser->id);
        $this->makeDetail($opname, [
            'batch_number' => 'BCH-BARU',
            'tanggal_expired' => '2027-12-31',
        ]);

        app(StokService::class)->prosesOpnameSelesai($opname->fresh(['details', 'fasilitas']));

        $this->assertSame(110, (int) $this->obat->stokFaskes()->where('fasilitas_id', $this->puskesmas->id)->value('jumlah'));
        $this->assertDatabaseHas('batch_stok', [
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->puskesmas->id,
            'batch_number' => 'BCH-BARU',
            'jumlah' => 10,
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => OpnameStok::class,
            'referensi_id' => $opname->id,
            'tipe' => 'opname',
            'jumlah' => 10,
        ]);
    }

    public function test_proses_opname_penyesuaian_mengurangi_stok_faskes(): void
    {
        $this->makeBatch($this->puskesmas->id, 100);
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
            'jumlah' => 100,
            'stok_minimum' => 0,
        ]);
        $opname = $this->makeOpname('penyesuaian', 'selesai', $this->puskesmas->id, $this->puskesmasUser->id);
        $this->makeDetail($opname, [
            'stok_fisik' => 90,
            'selisih' => -10,
            'batch_number' => 'BCH-OPNAME',
            'tanggal_expired' => '2027-08-20',
        ]);

        app(StokService::class)->prosesOpnameSelesai($opname->fresh('details'));

        $this->assertSame(90, (int) StokFaskes::where('fasilitas_id', $this->puskesmas->id)->where('obat_id', $this->obat->id)->value('jumlah'));
    }

    public function test_proses_opname_stok_awal_menambah_stok_gudang_dan_batch(): void
    {
        $opname = $this->makeOpname('stok_awal', 'selesai', null, $this->adminGudang->id);
        $this->makeDetail($opname, [
            'stok_sistem' => 0,
            'stok_fisik' => 50,
            'selisih' => 50,
            'batch_number' => 'BCH-AWAL',
            'tanggal_expired' => '2027-10-01',
        ]);

        app(StokService::class)->prosesOpnameSelesai($opname->fresh('details'));

        $this->assertSame(50, (int) StokGudang::where('obat_id', $this->obat->id)->value('jumlah'));
        $this->assertDatabaseHas('batch_stok', [
            'obat_id' => $this->obat->id,
            'fasilitas_id' => null,
            'batch_number' => 'BCH-AWAL',
            'jumlah' => 50,
        ]);
    }

    public function test_proses_opname_dengan_selisih_nol_tidak_mencatat_riwayat(): void
    {
        $opname = $this->makeOpname('penyesuaian', 'selesai', null, $this->adminGudang->id);
        $this->makeDetail($opname, ['stok_fisik' => 100, 'selisih' => 0]);

        app(StokService::class)->prosesOpnameSelesai($opname->fresh('details'));

        $this->assertDatabaseMissing('riwayat_stok', [
            'referensi_type' => OpnameStok::class,
            'referensi_id' => $opname->id,
        ]);
        $this->assertDatabaseCount('stok_gudang', 0);
    }

    public function test_reverse_opname_mengembalikan_stok_batch_dan_mencatat_riwayat(): void
    {
        $batch = $this->makeBatch($this->puskesmas->id, 100, 'BCH-REV');
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
            'jumlah' => 100,
            'stok_minimum' => 0,
        ]);
        $opname = $this->makeOpname('penyesuaian', 'selesai', $this->puskesmas->id, $this->puskesmasUser->id);
        $detail = $this->makeDetail($opname, [
            'batch_number' => 'BCH-REV',
            'tanggal_expired' => '2027-08-20',
        ]);

        app(StokService::class)->prosesOpnameSelesai($opname->fresh(['details', 'fasilitas']));
        app(StokService::class)->reverseOpname($opname, collect([$detail]));

        $this->assertSame(100, (int) StokFaskes::where('fasilitas_id', $this->puskesmas->id)->where('obat_id', $this->obat->id)->value('jumlah'));
        $this->assertSame(100, (int) $batch->fresh()->jumlah);
        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => OpnameStok::class,
            'referensi_id' => $opname->id,
            'tipe' => 'penyesuaian',
            'jumlah' => -10,
        ]);
    }

    public function test_resource_mengembalikan_semua_opname_yang_diizinkan(): void
    {
        $this->makeOpname('penyesuaian', 'draft', $this->puskesmas->id, $this->puskesmasUser->id);
        $this->makeOpname('stok_baru', 'selesai', null, $this->adminGudang->id);

        $this->actingAs($this->adminDinas);

        $this->assertSame(2, OpnameStokResource::getEloquentQuery()->count());
    }
}
