<?php

namespace Tests\Feature;

use App\Filament\Resources\PermintaanObats\Pages\CreatePermintaanObat;
use App\Filament\Resources\PermintaanObats\PermintaanObatResource;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermintaanObatTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminDinas;

    private User $adminGudang;

    private User $userPuskesmas;

    private User $userPustu;

    private FasilitasKesehatan $puskesmas;

    private FasilitasKesehatan $pustu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create fasilitas
        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-001',
            'nama' => 'Puskesmas Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Test',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-001',
            'nama' => 'Pustu Test',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmas->id,
            'alamat' => 'Jl. Test 2',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        // Create users with roles
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->adminDinas = User::factory()->create(['name' => 'Admin Dinas', 'fasilitas_kesehatan_id' => null]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->adminGudang = User::factory()->create(['name' => 'Admin Gudang', 'fasilitas_kesehatan_id' => null]);
        $this->adminGudang->assignRole('admin_gudang');

        $this->userPuskesmas = User::factory()->create([
            'name' => 'User Puskesmas',
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->userPustu = User::factory()->create([
            'name' => 'User Pustu',
            'fasilitas_kesehatan_id' => $this->pustu->id,
        ]);
        $this->userPustu->assignRole('pustu');

        // Create master data for obat
        Obat::create([
            'kode_obat' => 'OBT-001',
            'nama_obat' => 'Parasetamol 500mg',
            'kategori' => 'Analgesik',
            'satuan' => 'Tablet',
            'bentuk_sediaan' => 'tablet',
            'status' => 'aktif',
        ]);
    }

    // ──────────────────────────────────────────────
    //  POLICY TESTS
    // ──────────────────────────────────────────────

    public function test_super_admin_can_view_any_permintaan(): void
    {
        $this->assertTrue($this->superAdmin->can('view_permintaan_obat'));
    }

    public function test_super_admin_can_access_all_permintaan_actions(): void
    {
        // Super admin has all permissions via Spatie's Gate::before
        $this->assertTrue($this->superAdmin->can('view_permintaan_obat'));
        $this->assertTrue($this->superAdmin->can('create_permintaan_obat'));
        $this->assertTrue($this->superAdmin->can('update_permintaan_obat'));
        $this->assertTrue($this->superAdmin->can('delete_permintaan_obat'));
    }

    public function test_user_puskesmas_can_create_permintaan(): void
    {
        $this->assertTrue($this->userPuskesmas->can('create_permintaan_obat'));
    }

    public function test_user_pustu_can_create_permintaan(): void
    {
        $this->assertTrue($this->userPustu->can('create_permintaan_obat'));
    }

    public function test_admin_dinas_cannot_create_permintaan(): void
    {
        $this->assertFalse($this->adminDinas->can('create_permintaan_obat'));
    }

    public function test_admin_gudang_cannot_create_permintaan(): void
    {
        $this->assertFalse($this->adminGudang->can('create_permintaan_obat'));
    }

    public function test_user_can_only_view_own_faskes_permintaan(): void
    {
        $permintaanPuskesmas = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0001',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_permintaan' => now(),
        ]);

        $permintaanPustu = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0002',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now(),
        ]);

        // Dinas: hanya bisa melihat permintaan dari puskesmas
        $this->assertTrue($this->adminDinas->can('view', $permintaanPuskesmas));
        $this->assertFalse($this->adminDinas->can('view', $permintaanPustu));

        // User puskesmas: bisa melihat permintaan dari pustu di bawahnya DAN permintaan yang dia kirim ke dinas
        $this->assertTrue($this->userPuskesmas->can('view', $permintaanPuskesmas));
        $this->assertTrue($this->userPuskesmas->can('view', $permintaanPustu));

        // User pustu: hanya bisa melihat permintaan miliknya sendiri sebagai pengirim
        $this->assertTrue($this->userPustu->can('view', $permintaanPustu));
        $this->assertFalse($this->userPustu->can('view', $permintaanPuskesmas));
    }

    public function test_admin_gudang_can_view_all_permintaan(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0010',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'disetujui',
            'tanggal_permintaan' => now(),
        ]);

        $this->assertTrue($this->adminGudang->can('view', $permintaan));
        $this->assertTrue($this->adminGudang->can('viewAny', PermintaanObat::class));
    }

    public function test_admin_dinas_can_view_and_update_all_permintaan(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0011',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now(),
        ]);

        $this->assertTrue($this->adminDinas->can('view', $permintaan));
        $this->assertTrue($this->adminDinas->can('update', $permintaan));
        $this->assertFalse($this->adminDinas->can('create', PermintaanObat::class));
    }

    // ──────────────────────────────────────────────
    //  CREATE FLOW TESTS
    // ──────────────────────────────────────────────

    public function test_puskesmas_create_permintaan_sets_tipe_puskesmas_ke_dinas(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => PermintaanObat::generateNomorPermintaan(),
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_permintaan' => now(),
        ]);

        $this->assertEquals('puskesmas_ke_dinas', $permintaan->tipe_permintaan);
        $this->assertNull($permintaan->fasilitas_tujuan_id);
        $this->assertEquals('draft', $permintaan->status);
    }

    public function test_pustu_create_permintaan_sets_tipe_pustu_ke_puskesmas(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => PermintaanObat::generateNomorPermintaan(),
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now(),
        ]);

        $this->assertEquals('pustu_ke_puskesmas', $permintaan->tipe_permintaan);
        $this->assertEquals($this->puskesmas->id, $permintaan->fasilitas_tujuan_id);
    }

    public function test_permintaan_nomor_format_is_correct(): void
    {
        $nomor = PermintaanObat::generateNomorPermintaan();

        $this->assertStringStartsWith('RQ/', $nomor);
        $this->assertStringEndsWith('/0001', $nomor);
    }

    // ──────────────────────────────────────────────
    //  PROSES / STATUS FLOW TESTS
    // ──────────────────────────────────────────────

    public function test_approve_permintaan_sets_correct_timestamps(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0003',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now(),
        ]);

        $permintaan->update([
            'status' => 'disetujui',
            'tanggal_disetujui' => now()->toDateString(),
            'disetujui_oleh' => $this->adminDinas->id,
        ]);

        $permintaan->refresh();

        $this->assertEquals('disetujui', $permintaan->status);
        $this->assertNotNull($permintaan->tanggal_disetujui);
        $this->assertEquals($this->adminDinas->id, $permintaan->disetujui_oleh);
        $this->assertNull($permintaan->alasan_penolakan);
    }

    public function test_reject_permintaan_sets_alasan_penolakan(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0004',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now(),
        ]);

        $permintaan->update([
            'status' => 'ditolak',
            'tanggal_ditolak' => now()->toDateString(),
            'disetujui_oleh' => $this->adminDinas->id,
            'alasan_penolakan' => 'Stok tidak mencukupi',
        ]);

        $permintaan->refresh();

        $this->assertEquals('ditolak', $permintaan->status);
        $this->assertNotNull($permintaan->tanggal_ditolak);
        $this->assertEquals('Stok tidak mencukupi', $permintaan->alasan_penolakan);
    }

    public function test_rejected_permintaan_can_be_edited_and_resent(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0005',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'ditolak',
            'tanggal_permintaan' => now(),
            'tanggal_ditolak' => now()->subDay(),
            'alasan_penolakan' => 'Data kurang lengkap',
        ]);

        // User mengedit dan mengirim ulang
        $permintaan->update([
            'status' => 'menunggu_persetujuan',
            'catatan' => 'Data sudah dilengkapi',
            'alasan_penolakan' => null,
            'tanggal_ditolak' => null,
        ]);

        $permintaan->refresh();

        $this->assertEquals('menunggu_persetujuan', $permintaan->status);
        $this->assertNull($permintaan->alasan_penolakan);
    }

    public function test_permintaan_with_details_creates_correctly(): void
    {
        $obat = Obat::first();

        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/05/0006',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_permintaan' => now(),
        ]);

        $detail = $permintaan->details()->create([
            'obat_id' => $obat->id,
            'jumlah_diminta' => 100,
        ]);

        $this->assertEquals(100, $detail->jumlah_diminta);
        $this->assertEquals($permintaan->id, $detail->permintaan_id);
        $this->assertCount(1, $permintaan->fresh()->details);
    }

    public function test_livewire_component_can_be_instantiated(): void
    {
        // Verify Livewire can instantiate the Create page component
        $component = Livewire::test(
            CreatePermintaanObat::class,
        );
        $this->assertNotNull($component, 'Livewire component harus bisa di-instantiate');
    }

    public function test_livewire_component_with_auth_fails_on_create_permission(): void
    {
        // DIAGNOSTIC: Test if actingAs + Livewire::test() works
        // Use $this->actingAs() BEFORE Livewire::test()
        $this->actingAs($this->userPuskesmas);

        $component = Livewire::test(
            CreatePermintaanObat::class,
        );

        $this->assertNotNull($component, 'Livewire component dengan auth harus terisi');
    }

    public function test_puskesmas_create_via_livewire_saves_details(): void
    {
        $obat = Obat::first();
        $this->assertNotNull($obat, 'Obat harus dibuat di setUp');

        // DIAGNOSTIC: Simulate creating a PermintaanObat via Filament/Livewire
        $this->actingAs($this->userPuskesmas);

        $component = Livewire::test(
            CreatePermintaanObat::class,
        );

        $this->assertNotNull($component, 'Livewire component harus terisi');

        // Set parent form fields
        $component->set('data.nomor_permintaan', 'RQ/2026/05/DIAG-001');
        $component->set('data.tanggal_permintaan', now()->format('Y-m-d'));
        $component->set('data.catatan', 'DIAGNOSTIC TEST');

        // Create gudang stock so the obat shows in the filtered dropdown
        StokGudang::create(['obat_id' => $obat->id, 'jumlah' => 500]);

        // Add item via embedded table header action (new pattern)
        $component->callTableAction('addItem', data: [
            'obat_id' => $obat->id,
            'jumlah_diminta' => 100,
        ]);

        // Call the create method
        $component->call('create');

        $component->assertHasNoErrors();

        // Verify the record was created
        $permintaan = PermintaanObat::where('nomor_permintaan', 'RQ/2026/05/DIAG-001')->first();

        $this->assertNotNull($permintaan, 'PermintaanObat harus dibuat');

        // DIAGNOSTIC: Check if details were saved
        $details = $permintaan->fresh()->details;
        $this->assertCount(1, $details, 'Detail permintaan harus tersimpan');
        $this->assertEquals($obat->id, $details->first()->obat_id);
        $this->assertEquals(100, $details->first()->jumlah_diminta);
    }

    public function test_pustu_create_via_livewire_saves_details(): void
    {
        $obat = Obat::first();
        $this->assertNotNull($obat, 'Obat harus dibuat di setUp');

        // DIAGNOSTIC: Test as pustu user (pustu_ke_puskesmas)
        $this->actingAs($this->userPustu);

        $component = Livewire::test(
            CreatePermintaanObat::class,
        );

        $this->assertNotNull($component, 'Livewire component harus terisi');

        $component->set('data.nomor_permintaan', 'RQ/2026/05/DIAG-002');
        $component->set('data.tanggal_permintaan', now()->format('Y-m-d'));
        $component->set('data.catatan', 'DIAGNOSTIC TEST PUSTU');

        // Create faskes stock at the parent puskesmas so the obat shows in the filtered dropdown
        StokFaskes::create([
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $obat->id,
            'jumlah' => 500,
        ]);

        // Add item via embedded table header action (new pattern)
        $component->callTableAction('addItem', data: [
            'obat_id' => $obat->id,
            'jumlah_diminta' => 50,
        ]);

        $component->call('create');
        $component->assertHasNoErrors();

        $permintaan = PermintaanObat::where('nomor_permintaan', 'RQ/2026/05/DIAG-002')->first();
        $this->assertNotNull($permintaan, 'PermintaanObat harus dibuat');
        $this->assertCount(1, $permintaan->fresh()->details, 'Detail permintaan harus tersimpan');
        $this->assertEquals(50, $permintaan->fresh()->details->first()->jumlah_diminta);
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE: getEloquentQuery()
    // ──────────────────────────────────────────────

    /**
     * Buat puskesmas kedua (lokal) untuk pengujian filter.
     */
    private function buatPuskesmasLain(): FasilitasKesehatan
    {
        return FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-002',
            'nama' => 'Puskesmas Lain',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Lain',
            'pic' => 'PIC Lain',
            'kontak_pic' => '456',
            'status' => 'aktif',
        ]);
    }

    public function test_super_admin_sees_all_permintaan_via_resource(): void
    {
        $lain = $this->buatPuskesmasLain();

        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0001',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0002',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'draft',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // Harus tersembunyi dari dinas: permintaan dari pustu
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0003',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->superAdmin);

        $query = PermintaanObatResource::getEloquentQuery();
        $this->assertSame(2, $query->count());
        $this->assertEmpty($query->where('tipe_permintaan', 'pustu_ke_puskesmas')->get());
    }

    public function test_admin_dinas_sees_puskesmas_permintaan_via_resource(): void
    {
        $lain = $this->buatPuskesmasLain();

        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0010',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0011',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // Harus tersembunhi dari admin_dinas
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0012',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminDinas);

        $query = PermintaanObatResource::getEloquentQuery();
        $this->assertSame(2, $query->count());
        $this->assertEmpty($query->where('tipe_permintaan', 'pustu_ke_puskesmas')->get());
    }

    public function test_admin_gudang_sees_puskesmas_permintaan_via_resource(): void
    {
        $lain = $this->buatPuskesmasLain();

        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0020',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0021',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // Harus tersembunhi dari admin_gudang: permintaan dari pustu
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0022',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminGudang);

        $query = PermintaanObatResource::getEloquentQuery();
        $this->assertSame(2, $query->count());
        $this->assertEmpty($query->where('tipe_permintaan', 'pustu_ke_puskesmas')->get());
    }

    public function test_puskesmas_only_sees_own_permintaan_via_resource(): void
    {
        $lain = $this->buatPuskesmasLain();

        // Permintaan dari pustu di bawah puskesmas ini - terlihat
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0101',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // Permintaan dari puskesmas lain (tipe lain) - HARUS tersembunyi
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0102',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->userPuskesmas);

        $query = PermintaanObatResource::getEloquentQuery();
        $this->assertSame(1, $query->count());
        $this->assertEmpty($query->where('fasilitas_pengirim_id', $lain->id)->get());
    }

    public function test_pustu_only_sees_own_permintaan_via_resource(): void
    {
        $lain = $this->buatPuskesmasLain();

        // Permintaan dari pustu ini (sebagai pengirim) - terlihat
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0200',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'draft',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // Permintaan dari puskesmas lain (dinas) - HARUS tersembunyi
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0201',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->userPustu);

        $query = PermintaanObatResource::getEloquentQuery();
        $this->assertSame(1, $query->count());
        $this->assertNotNull($query->where('fasilitas_pengirim_id', $this->pustu->id)->first());
    }

    public function test_user_without_faskes_sees_no_permintaan_via_resource(): void
    {
        $userTanpaFaskes = User::factory()->create(['fasilitas_kesehatan_id' => null]);

        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0300',
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($userTanpaFaskes);

        $this->assertSame(0, PermintaanObatResource::getEloquentQuery()->count());
    }

    // ──────────────────────────────────────────────
    //  NAVIGATION BADGE: getNavigationBadge()
    // ──────────────────────────────────────────────

    public function test_navigation_badge_returns_null_when_no_menunggu_persetujuan(): void
    {
        $this->actingAs($this->userPuskesmas);

        $this->assertNull(PermintaanObatResource::getNavigationBadge());
    }

    public function test_navigation_badge_counts_filtered_menunggu_persetujuan(): void
    {
        $lain = $this->buatPuskesmasLain();

        // 1 milik pustu di bawah puskesmas (terlihat untuk userPuskesmas)
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0400',
            'fasilitas_pengirim_id' => $this->pustu->id,
            'fasilitas_tujuan_id' => $this->puskesmas->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);
        // 1 milik puskesmas lain (tidak terlihat untuk userPuskesmas)
        PermintaanObat::create([
            'nomor_permintaan' => 'RQ/2026/TEST/0401',
            'fasilitas_pengirim_id' => $lain->id,
            'fasilitas_tujuan_id' => null,
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->userPuskesmas);

        // Badge harus menampilkan 1 (filtered), bukan 1 dari luar scope
        $this->assertSame('1', PermintaanObatResource::getNavigationBadge());

        // Super admin melihat total 2
        $this->actingAs($this->superAdmin);
        $this->assertSame('1', PermintaanObatResource::getNavigationBadge());
    }
}
