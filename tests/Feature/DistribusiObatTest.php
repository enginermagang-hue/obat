<?php

namespace Tests\Feature;

use App\Filament\Resources\DistribusiObats\DistribusiObatResource;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistribusiObatTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminDinas;

    private User $adminGudang;

    private User $userPuskesmas;

    private User $userPustu;

    private FasilitasKesehatan $puskesmasA;

    private FasilitasKesehatan $puskesmasB;

    private FasilitasKesehatan $pustu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Faskes fixtures
        $this->puskesmasA = FasilitasKesehatan::create([
            'parent_id' => null,
            'kode_faskes' => 'PKM-A',
            'nama' => 'Puskesmas A',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. A',
            'pic' => 'Test PIC A',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->puskesmasB = FasilitasKesehatan::create([
            'parent_id' => null,
            'kode_faskes' => 'PKM-B',
            'nama' => 'Puskesmas B',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. B',
            'pic' => 'Test PIC B',
            'kontak_pic' => '456',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-001',
            'nama' => 'Pustu 001',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmasA->id,
            'alamat' => 'Jl. Pustu',
            'pic' => 'Test PIC Pustu',
            'kontak_pic' => '789',
            'status' => 'aktif',
        ]);

        // Users
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->adminDinas = User::factory()->create(['name' => 'Admin Dinas', 'fasilitas_kesehatan_id' => null]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->adminGudang = User::factory()->create(['name' => 'Admin Gudang', 'fasilitas_kesehatan_id' => null]);
        $this->adminGudang->assignRole('admin_gudang');

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

    /**
     * Buat record distribusi untuk pengujian. Permintaan dikosongkan (nullable).
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeDistribusi(array $overrides = []): DistribusiObat
    {
        return DistribusiObat::create(array_merge([
            'nomor_surat_jalan' => 'SJ/'.uniqid(),
            'permintaan_id' => null,
            'tipe_distribusi' => 'puskesmas_ke_pustu',
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustu->id,
            'status' => 'draft',
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->superAdmin->id,
            'penerima_id' => null,
        ], $overrides));
    }

    // ──────────────────────────────────────────────
    //  POLICY: viewAny (akses halaman index)
    // ──────────────────────────────────────────────

    public function test_super_admin_can_view_any_distribusi(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', DistribusiObat::class));
    }

    public function test_admin_dinas_can_view_any_distribusi(): void
    {
        $this->assertTrue($this->adminDinas->can('viewAny', DistribusiObat::class));
    }

    public function test_admin_gudang_can_view_any_distribusi(): void
    {
        $this->assertTrue($this->adminGudang->can('viewAny', DistribusiObat::class));
    }

    public function test_puskesmas_can_view_any_distribusi(): void
    {
        $this->assertTrue($this->userPuskesmas->can('viewAny', DistribusiObat::class));
    }

    public function test_pustu_can_view_any_distribusi(): void
    {
        $this->assertTrue($this->userPustu->can('viewAny', DistribusiObat::class));
    }

    // ──────────────────────────────────────────────
    //  POLICY: view (record-level access)
    // ──────────────────────────────────────────────

    public function test_puskesmas_can_view_own_distribusi_as_pengirim(): void
    {
        $distribusi = $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustu->id,
        ]);

        $this->assertTrue($this->userPuskesmas->can('view', $distribusi));
    }

    public function test_puskesmas_can_view_own_distribusi_as_penerima(): void
    {
        $distribusi = $this->makeDistribusi([
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null,
            'fasilitas_penerima_id' => $this->puskesmasA->id,
        ]);

        $this->assertTrue($this->userPuskesmas->can('view', $distribusi));
    }

    public function test_puskesmas_cannot_view_other_puskesmas_distribusi(): void
    {
        $distribusi = $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasB->id,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
        ]);

        $this->assertFalse($this->userPuskesmas->can('view', $distribusi));
    }

    public function test_pustu_can_view_distribusi_as_penerima(): void
    {
        $distribusi = $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustu->id,
        ]);

        $this->assertTrue($this->userPustu->can('view', $distribusi));
    }

    public function test_pustu_cannot_view_distribusi_unrelated_to_faskes(): void
    {
        $distribusi = $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasB->id,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
        ]);

        $this->assertFalse($this->userPustu->can('view', $distribusi));
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE: getEloquentQuery()
    // ──────────────────────────────────────────────

    public function test_super_admin_sees_all_distribusi_via_resource(): void
    {
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->pustu->id]);
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->puskesmasB->id]);
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->puskesmasA->id]);

        $this->actingAs($this->superAdmin);

        $this->assertSame(3, DistribusiObatResource::getEloquentQuery()->count());
    }

    public function test_admin_dinas_sees_all_distribusi_via_resource(): void
    {
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->pustu->id]);
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->puskesmasB->id]);

        $this->actingAs($this->adminDinas);

        $this->assertSame(2, DistribusiObatResource::getEloquentQuery()->count());
    }

    public function test_admin_gudang_sees_all_distribusi_via_resource(): void
    {
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->pustu->id]);
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->puskesmasB->id]);

        $this->actingAs($this->adminGudang);

        $this->assertSame(2, DistribusiObatResource::getEloquentQuery()->count());
    }

    public function test_puskesmas_only_sees_own_distribusi_via_resource(): void
    {
        // Distribusi Puskesmas A → Pustu A (involves puskesmasA)
        $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustu->id,
        ]);
        // Distribusi dinas → Puskesmas A (involves puskesmasA as penerima)
        $this->makeDistribusi([
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null,
            'fasilitas_penerima_id' => $this->puskesmasA->id,
        ]);
        // Distribusi lain yang tidak melibatkan puskesmasA (harus tersembunyi)
        $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasB->id,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
        ]);

        $this->actingAs($this->userPuskesmas);

        $query = DistribusiObatResource::getEloquentQuery();
        $this->assertSame(2, $query->count());
        $this->assertEmpty($query->where('fasilitas_pengirim_id', $this->puskesmasB->id)->get());
    }

    public function test_pustu_only_sees_distribusi_where_pustu_is_penerima(): void
    {
        // Distribusi Puskesmas A → Pustu (pustu adalah PENERIMA, harus terlihat)
        $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustu->id,
        ]);
        // Distribusi yang tidak melibatkan pustu (harus tersembunyi)
        $this->makeDistribusi([
            'fasilitas_pengirim_id' => $this->puskesmasB->id,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
        ]);

        $this->actingAs($this->userPustu);

        $query = DistribusiObatResource::getEloquentQuery();
        $this->assertSame(1, $query->count());
        $this->assertNotNull($query->where('fasilitas_penerima_id', $this->pustu->id)->first());
    }

    public function test_user_without_faskes_sees_no_distribusi_via_resource(): void
    {
        $userTanpaFaskes = User::factory()->create(['fasilitas_kesehatan_id' => null]);

        $this->makeDistribusi(['fasilitas_penerima_id' => $this->pustu->id]);
        $this->makeDistribusi(['fasilitas_penerima_id' => $this->puskesmasB->id]);

        $this->actingAs($userTanpaFaskes);

        $this->assertSame(0, DistribusiObatResource::getEloquentQuery()->count());
    }
}
