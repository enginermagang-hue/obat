<?php

namespace Tests\Feature;

use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\User;
use App\Policies\LaporanLplpoPolicy;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanLplpoPolicyTest extends TestCase
{
    use RefreshDatabase;

    private LaporanLplpoPolicy $policy;

    private FasilitasKesehatan $puskesmas;

    private FasilitasKesehatan $pustu;

    private FasilitasKesehatan $puskesmasLain;

    private LaporanLplpo $lplpoPustuKePuskesmas;

    private LaporanLplpo $lplpoPuskesmasKeDinas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->policy = new LaporanLplpoPolicy;

        // Fasilitas
        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-POL-001',
            'nama' => 'Puskesmas Pol Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'PIC',
            'kontak_pic' => '081',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-POL-001',
            'nama' => 'Pustu Pol Test',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmas->id,
            'alamat' => 'Jl. Test 2',
            'pic' => 'PIC',
            'kontak_pic' => '082',
            'status' => 'aktif',
        ]);

        $this->puskesmasLain = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-POL-002',
            'nama' => 'Puskesmas Lain',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test 3',
            'pic' => 'PIC',
            'kontak_pic' => '083',
            'status' => 'aktif',
        ]);

        // LPLPO pustu → puskesmas (status diajukan)
        $this->lplpoPustuKePuskesmas = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-POL-PUSTU',
            'fasilitas_id' => $this->pustu->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'tipe_pengajuan' => 'pustu_ke_puskesmas',
            'status' => 'diajukan',
            'tanggal_pembuatan' => now(),
            'tanggal_pengajuan' => now(),
            'dibuat_oleh' => $this->createUserWithRole('pustu', $this->pustu->id)->id,
        ]);

        // LPLPO puskesmas → dinas (status diajukan)
        $this->lplpoPuskesmasKeDinas = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-POL-DINAS',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 6,
            'periode_tahun' => 2026,
            'tipe_pengajuan' => 'puskesmas_ke_dinas',
            'status' => 'diajukan',
            'tanggal_pembuatan' => now(),
            'tanggal_pengajuan' => now(),
            'dibuat_oleh' => $this->createUserWithRole('puskesmas', $this->puskesmas->id)->id,
        ]);
    }

    // ──────── viewAny ────────

    public function test_super_admin_can_view_any_lplpo(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_admin_gudang_can_view_any_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_gudang');
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_admin_dinas_can_view_any_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_dinas');
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_puskesmas_can_view_any_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_pustu_can_view_any_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertTrue($this->policy->viewAny($user));
    }

    // ──────── view ────────

    public function test_super_admin_can_view_all_lplpo(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $this->assertTrue($this->policy->view($user, $this->lplpoPustuKePuskesmas));
        $this->assertTrue($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_admin_gudang_can_view_all_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_gudang');
        $this->assertTrue($this->policy->view($user, $this->lplpoPustuKePuskesmas));
        $this->assertTrue($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_admin_dinas_can_view_all_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_dinas');
        $this->assertTrue($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
        $this->assertTrue($this->policy->view($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_puskesmas_can_view_own_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertTrue($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_puskesmas_can_view_pustu_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertTrue($this->policy->view($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_puskesmas_cannot_view_other_puskesmas_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmasLain->id);
        $this->assertFalse($this->policy->view($user, $this->lplpoPustuKePuskesmas));
        $this->assertFalse($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_pustu_can_view_own_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertTrue($this->policy->view($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_pustu_cannot_view_puskesmas_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertFalse($this->policy->view($user, $this->lplpoPuskesmasKeDinas));
    }

    // ──────── create ────────

    public function test_super_admin_cannot_create_lplpo(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_dinas_cannot_create_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_dinas');
        $this->assertFalse($this->policy->create($user));
    }

    public function test_admin_gudang_cannot_create_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_gudang');
        $this->assertFalse($this->policy->create($user));
    }

    public function test_puskesmas_can_create_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertTrue($this->policy->create($user));
    }

    public function test_pustu_can_create_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertTrue($this->policy->create($user));
    }

    // ──────── update ────────

    public function test_super_admin_cannot_update_lplpo(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $this->assertFalse($this->policy->update($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_admin_dinas_cannot_update_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_dinas');
        $this->assertFalse($this->policy->update($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_admin_gudang_cannot_update_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_gudang');
        $this->assertFalse($this->policy->update($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_puskesmas_can_update_own_draft_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $lplpo = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-POL-UPD',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'tipe_pengajuan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $user->id,
        ]);

        $this->assertTrue($this->policy->update($user, $lplpo));
    }

    public function test_puskesmas_cannot_update_submitted_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertFalse($this->policy->update($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_pustu_cannot_update_puskesmas_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertFalse($this->policy->update($user, $this->lplpoPuskesmasKeDinas));
    }

    // ──────── delete ────────

    public function test_super_admin_cannot_delete_lplpo(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $this->assertFalse($this->policy->delete($user, $this->lplpoPustuKePuskesmas));
    }

    public function test_admin_dinas_cannot_delete_lplpo(): void
    {
        $user = $this->createUserWithRole('admin_dinas');
        $this->assertFalse($this->policy->delete($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_puskesmas_can_delete_own_draft_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $lplpo = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO-POL-DEL',
            'fasilitas_id' => $this->puskesmas->id,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'tipe_pengajuan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_pembuatan' => now(),
            'dibuat_oleh' => $user->id,
        ]);

        $this->assertTrue($this->policy->delete($user, $lplpo));
    }

    public function test_puskesmas_cannot_delete_submitted_lplpo(): void
    {
        $user = $this->createUserWithRole('puskesmas', $this->puskesmas->id);
        $this->assertFalse($this->policy->delete($user, $this->lplpoPuskesmasKeDinas));
    }

    public function test_pustu_cannot_delete_puskesmas_lplpo(): void
    {
        $user = $this->createUserWithRole('pustu', $this->pustu->id);
        $this->assertFalse($this->policy->delete($user, $this->lplpoPuskesmasKeDinas));
    }

    // ──────── helpers ────────

    private function createUserWithRole(string $role, ?int $faskesId = null): User
    {
        $user = User::factory()->create([
            'fasilitas_kesehatan_id' => $faskesId,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
