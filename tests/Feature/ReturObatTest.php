<?php

namespace Tests\Feature;

use App\Filament\Resources\ReturObats\Pages\ViewReturObat;
use App\Filament\Resources\ReturObats\ReturObatResource;
use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\ReturObat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturObatTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminDinas;

    private User $adminGudang;

    private User $userPuskesmasA;

    private User $userPuskesmasB;

    private User $userPustu;

    private FasilitasKesehatan $puskesmasA;

    private FasilitasKesehatan $puskesmasB;

    private FasilitasKesehatan $pustuA;

    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->puskesmasA = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-A',
            'nama' => 'Puskesmas A',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. A',
            'pic' => 'Test PIC A',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->puskesmasB = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-B',
            'nama' => 'Puskesmas B',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. B',
            'pic' => 'Test PIC B',
            'kontak_pic' => '456',
            'status' => 'aktif',
        ]);

        $this->pustuA = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-A',
            'nama' => 'Pustu A',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmasA->id,
            'alamat' => 'Jl. Pustu',
            'pic' => 'Test PIC Pustu',
            'kontak_pic' => '789',
            'status' => 'aktif',
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->adminDinas = User::factory()->create(['fasilitas_kesehatan_id' => null]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->adminGudang = User::factory()->create(['fasilitas_kesehatan_id' => null]);
        $this->adminGudang->assignRole('admin_gudang');

        $this->userPuskesmasA = User::factory()->create(['fasilitas_kesehatan_id' => $this->puskesmasA->id]);
        $this->userPuskesmasA->assignRole('puskesmas');

        $this->userPuskesmasB = User::factory()->create(['fasilitas_kesehatan_id' => $this->puskesmasB->id]);
        $this->userPuskesmasB->assignRole('puskesmas');

        $this->userPustu = User::factory()->create(['fasilitas_kesehatan_id' => $this->pustuA->id]);
        $this->userPustu->assignRole('pustu');

        $this->obat = Obat::factory()->create();
    }

    /**
     * Buat batch stok di faskes tertentu, atau di gudang jika fasilitasId null.
     */
    private function makeBatch(?int $fasilitasId, int $jumlah): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $fasilitasId,
            'batch_number' => 'BCH-'.uniqid(),
            'tanggal_expired' => now()->addYear()->toDateString(),
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => now()->toDateString(),
        ]);
    }

    private function makeStokFaskes(int $fasilitasId, int $jumlah): StokFaskes
    {
        return StokFaskes::create([
            'fasilitas_id' => $fasilitasId,
            'obat_id' => $this->obat->id,
            'jumlah' => $jumlah,
            'stok_minimum' => 0,
        ]);
    }

    private function makeRetur(
        string $tipe,
        string $status,
        ?int $pengirimId,
        ?int $penerimaId,
        ?BatchStok $batch = null,
        int $jumlahRetur = 10,
    ): ReturObat {
        $retur = ReturObat::create([
            'tipe_retur' => $tipe,
            'alasan' => 'rusak',
            'status' => $status,
            'fasilitas_pengirim_id' => $pengirimId,
            'fasilitas_penerima_id' => $penerimaId,
            'tanggal_retur' => now()->toDateString(),
        ]);

        if ($batch !== null) {
            $retur->details()->create([
                'obat_id' => $batch->obat_id,
                'batch_id' => $batch->id,
                'jumlah_retur' => $jumlahRetur,
            ]);
        }

        return $retur->fresh(['details', 'details.batch']);
    }

    // ──────────────────────────────────────────────
    //  NOMOR RETUR
    // ──────────────────────────────────────────────

    public function test_nomor_retur_auto_generate_saat_blank(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertStringStartsWith('RET/', $retur->nomor_retur);
        $this->assertStringContainsString((string) now()->format('Y'), $retur->nomor_retur);
    }

    public function test_nomor_retur_eksplisit_dipertahankan(): void
    {
        $retur = ReturObat::create([
            'nomor_retur' => 'RET-CUSTOM-001',
            'tipe_retur' => 'puskesmas_ke_gudang',
            'alasan' => 'rusak',
            'status' => 'draft',
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'tanggal_retur' => now()->toDateString(),
        ]);

        $this->assertSame('RET-CUSTOM-001', $retur->fresh()->nomor_retur);
    }

    // ──────────────────────────────────────────────
    //  POLICY: viewAny
    // ──────────────────────────────────────────────

    public function test_super_admin_can_view_any_retur(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', ReturObat::class));
    }

    public function test_admin_dinas_can_view_any_retur(): void
    {
        $this->assertTrue($this->adminDinas->can('viewAny', ReturObat::class));
    }

    public function test_admin_gudang_can_view_any_retur(): void
    {
        $this->assertTrue($this->adminGudang->can('viewAny', ReturObat::class));
    }

    public function test_puskesmas_can_view_any_retur(): void
    {
        $this->assertTrue($this->userPuskesmasA->can('viewAny', ReturObat::class));
    }

    public function test_pustu_can_view_any_retur(): void
    {
        $this->assertTrue($this->userPustu->can('viewAny', ReturObat::class));
    }

    // ──────────────────────────────────────────────
    //  POLICY: view (record-level)
    // ──────────────────────────────────────────────

    public function test_super_admin_can_view_any_retur_record(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertTrue($this->superAdmin->can('view', $retur));
    }

    public function test_admin_dinas_can_view_any_retur_record(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertTrue($this->adminDinas->can('view', $retur));
    }

    public function test_pengirim_faskes_can_view_own_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertTrue($this->userPuskesmasA->can('view', $retur));
    }

    public function test_penerima_faskes_can_view_own_retur(): void
    {
        $retur = $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasA->id);

        $this->assertTrue($this->userPuskesmasA->can('view', $retur));
    }

    public function test_faskes_lain_cannot_view_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertFalse($this->userPuskesmasB->can('view', $retur));
    }

    // ──────────────────────────────────────────────
    //  POLICY: create
    // ──────────────────────────────────────────────

    public function test_super_admin_can_create_retur(): void
    {
        $this->assertTrue($this->superAdmin->can('create', ReturObat::class));
    }

    public function test_puskesmas_with_faskes_can_create_retur(): void
    {
        $this->assertTrue($this->userPuskesmasA->can('create', ReturObat::class));
    }

    public function test_pustu_cannot_create_retur(): void
    {
        $this->assertFalse($this->userPustu->can('create', ReturObat::class));
    }

    public function test_admin_dinas_cannot_create_retur(): void
    {
        $this->assertFalse($this->adminDinas->can('create', ReturObat::class));
    }

    public function test_admin_gudang_can_create_retur(): void
    {
        $this->assertTrue($this->adminGudang->can('create', ReturObat::class));
    }

    // ──────────────────────────────────────────────
    //  POLICY: update
    // ──────────────────────────────────────────────

    public function test_super_admin_can_update_any_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'disetujui', $this->puskesmasA->id, null);

        $this->assertTrue($this->superAdmin->can('update', $retur));
    }

    public function test_admin_dinas_can_update_retur_menunggu_approval(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'menunggu_approval', $this->puskesmasA->id, null);

        $this->assertTrue($this->adminDinas->can('update', $retur));
    }

    public function test_admin_gudang_can_update_retur_gudang(): void
    {
        $retur = $this->makeRetur('gudang_ke_supplier', 'draft', null, null);

        $this->assertTrue($this->adminGudang->can('update', $retur));
    }

    public function test_pengirim_puskesmas_can_update_draft_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertTrue($this->userPuskesmasA->can('update', $retur));
    }

    public function test_pengirim_puskesmas_cannot_update_disetujui_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'disetujui', $this->puskesmasA->id, null);

        $this->assertFalse($this->userPuskesmasA->can('update', $retur));
    }

    public function test_penerima_faskes_can_update_dalam_pengiriman_retur(): void
    {
        $retur = $this->makeRetur('pustu_ke_puskesmas', 'dalam_pengiriman', $this->pustuA->id, $this->puskesmasA->id);

        $this->assertTrue($this->userPuskesmasA->can('update', $retur));
    }

    public function test_penerima_faskes_cannot_update_draft_retur(): void
    {
        $retur = $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasA->id);

        $this->assertFalse($this->userPuskesmasA->can('update', $retur));
    }

    public function test_faskes_lain_cannot_update_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertFalse($this->userPuskesmasB->can('update', $retur));
    }

    // ──────────────────────────────────────────────
    //  POLICY: delete
    // ──────────────────────────────────────────────

    public function test_super_admin_can_delete_any_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'menunggu_approval', $this->puskesmasA->id, null);

        $this->assertTrue($this->superAdmin->can('delete', $retur));
    }

    public function test_pengirim_can_delete_own_draft_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertTrue($this->userPuskesmasA->can('delete', $retur));
    }

    public function test_pengirim_cannot_delete_non_draft_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'menunggu_approval', $this->puskesmasA->id, null);

        $this->assertFalse($this->userPuskesmasA->can('delete', $retur));
    }

    public function test_faskes_lain_cannot_delete_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertFalse($this->userPuskesmasB->can('delete', $retur));
    }

    public function test_admin_dinas_cannot_delete_retur(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->assertFalse($this->adminDinas->can('delete', $retur));
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE: getEloquentQuery()
    // ──────────────────────────────────────────────

    public function test_admin_roles_see_all_retur_via_resource(): void
    {
        $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);
        $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasA->id);
        $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasB->id, null);

        foreach ([$this->superAdmin, $this->adminDinas, $this->adminGudang] as $admin) {
            $this->actingAs($admin);
            $this->assertSame(3, ReturObatResource::getEloquentQuery()->count());
        }
    }

    public function test_puskesmas_sees_only_own_retur_via_resource(): void
    {
        $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);
        $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasA->id);
        $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasB->id, null);

        $this->actingAs($this->userPuskesmasA);

        $this->assertSame(2, ReturObatResource::getEloquentQuery()->count());
    }

    public function test_pustu_sees_only_own_pustu_retur_via_resource(): void
    {
        $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);
        $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasA->id);
        $this->makeRetur('pustu_ke_puskesmas', 'draft', $this->pustuA->id, $this->puskesmasB->id);

        $this->actingAs($this->userPustu);

        $query = ReturObatResource::getEloquentQuery();

        $this->assertSame(2, $query->count());
        $query->get()->each(function (ReturObat $retur): void {
            $this->assertSame('pustu_ke_puskesmas', $retur->tipe_retur);
            $this->assertSame($this->pustuA->id, (int) $retur->fasilitas_pengirim_id);
        });
    }

    // ──────────────────────────────────────────────
    //  STOK SERVICE: prosesReturDiterima
    // ──────────────────────────────────────────────

    public function test_proses_retur_puskesmas_ke_gudang_menggeser_stok(): void
    {
        $batch = $this->makeBatch($this->puskesmasA->id, 50);
        $this->makeStokFaskes($this->puskesmasA->id, 50);

        $retur = $this->makeRetur('puskesmas_ke_gudang', 'dalam_pengiriman', $this->puskesmasA->id, null, $batch, 20);

        $this->actingAs($this->userPuskesmasA);
        app(StokService::class)->prosesReturDiterima($retur);

        $batchPengirim = $batch->fresh();
        $this->assertSame(30, (int) $batchPengirim->jumlah);
        $this->assertSame('tersedia', $batchPengirim->status);

        $stokFaskes = StokFaskes::where('fasilitas_id', $this->puskesmasA->id)
            ->where('obat_id', $this->obat->id)
            ->first();
        $this->assertSame(30, (int) $stokFaskes->jumlah);

        $stokGudang = StokGudang::where('obat_id', $this->obat->id)->first();
        $this->assertNotNull($stokGudang);
        $this->assertSame(20, (int) $stokGudang->jumlah);

        $batchGudang = BatchStok::whereNull('fasilitas_id')
            ->where('obat_id', $this->obat->id)
            ->where('batch_number', $batch->batch_number)
            ->first();
        $this->assertNotNull($batchGudang);
        $this->assertSame(20, (int) $batchGudang->jumlah);

        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => ReturObat::class,
            'referensi_id' => $retur->id,
            'tipe' => 'keluar',
            'fasilitas_id' => $this->puskesmasA->id,
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => ReturObat::class,
            'referensi_id' => $retur->id,
            'tipe' => 'masuk',
            'fasilitas_id' => null,
        ]);
    }

    public function test_proses_retur_pustu_ke_puskesmas_menggeser_stok(): void
    {
        $batch = $this->makeBatch($this->pustuA->id, 40);
        $this->makeStokFaskes($this->pustuA->id, 40);

        $retur = $this->makeRetur('pustu_ke_puskesmas', 'dalam_pengiriman', $this->pustuA->id, $this->puskesmasA->id, $batch, 15);

        $this->actingAs($this->userPustu);
        app(StokService::class)->prosesReturDiterima($retur);

        $this->assertSame(25, (int) $batch->fresh()->jumlah);

        $stokPustu = StokFaskes::where('fasilitas_id', $this->pustuA->id)
            ->where('obat_id', $this->obat->id)
            ->first();
        $this->assertSame(25, (int) $stokPustu->jumlah);

        $stokPenerima = StokFaskes::where('fasilitas_id', $this->puskesmasA->id)
            ->where('obat_id', $this->obat->id)
            ->first();
        $this->assertNotNull($stokPenerima);
        $this->assertSame(15, (int) $stokPenerima->jumlah);

        $batchPenerima = BatchStok::where('fasilitas_id', $this->puskesmasA->id)
            ->where('obat_id', $this->obat->id)
            ->where('batch_number', $batch->batch_number)
            ->first();
        $this->assertNotNull($batchPenerima);
        $this->assertSame(15, (int) $batchPenerima->jumlah);

        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => ReturObat::class,
            'referensi_id' => $retur->id,
            'tipe' => 'masuk',
            'fasilitas_id' => $this->puskesmasA->id,
        ]);
    }

    public function test_proses_retur_gudang_ke_supplier_mengurangi_stok_gudang_saja(): void
    {
        $batch = $this->makeBatch(null, 100);
        StokGudang::create([
            'obat_id' => $this->obat->id,
            'jumlah' => 100,
            'stok_minimum' => 0,
        ]);

        $retur = $this->makeRetur('gudang_ke_supplier', 'dalam_pengiriman', null, null, $batch, 100);

        $this->actingAs($this->adminGudang);
        app(StokService::class)->prosesReturDiterima($retur);

        $batchGudang = $batch->fresh();
        $this->assertSame(0, (int) $batchGudang->jumlah);
        $this->assertSame('dimusnahkan', $batchGudang->status);

        $stokGudang = StokGudang::where('obat_id', $this->obat->id)->first();
        $this->assertSame(0, (int) $stokGudang->jumlah);

        $this->assertDatabaseCount('stok_faskes', 0);
        $this->assertDatabaseMissing('riwayat_stok', [
            'referensi_type' => ReturObat::class,
            'referensi_id' => $retur->id,
            'tipe' => 'masuk',
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'referensi_type' => ReturObat::class,
            'referensi_id' => $retur->id,
            'tipe' => 'keluar',
            'fasilitas_id' => null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  TRANSISI STATUS via Livewire (ViewReturObat)
    // ──────────────────────────────────────────────

    public function test_ajukan_action_mengubah_status_ke_menunggu_approval(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'draft', $this->puskesmasA->id, null);

        $this->actingAs($this->userPuskesmasA);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('ajukan')
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('menunggu_approval', $retur->status);
    }

    public function test_setujui_action_mengisi_tanggal_dan_penyetuju(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'menunggu_approval', $this->puskesmasA->id, null);

        $this->actingAs($this->adminDinas);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('setujui')
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('disetujui', $retur->status);
        $this->assertNotNull($retur->tanggal_disetujui);
        $this->assertSame($this->adminDinas->id, (int) $retur->disetujui_oleh);
    }

    public function test_tolak_action_mencatat_alasan_penolakan(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'menunggu_approval', $this->puskesmasA->id, null);

        $this->actingAs($this->adminDinas);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('tolak', data: ['alasan_penolakan' => 'Jumlah tidak sesuai'])
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('ditolak', $retur->status);
        $this->assertNotNull($retur->tanggal_ditolak);
        $this->assertStringContainsString('Alasan penolakan: Jumlah tidak sesuai', $retur->catatan);
    }

    public function test_kirim_action_mengubah_status_ke_dalam_pengiriman(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'disetujui', $this->puskesmasA->id, null);

        $this->actingAs($this->adminGudang);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('kirim')
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('dalam_pengiriman', $retur->status);
        $this->assertNotNull($retur->tanggal_dikirim);
    }

    public function test_terima_action_memproses_stok(): void
    {
        $batch = $this->makeBatch($this->puskesmasA->id, 30);
        $this->makeStokFaskes($this->puskesmasA->id, 30);

        $retur = $this->makeRetur('puskesmas_ke_gudang', 'dalam_pengiriman', $this->puskesmasA->id, null, $batch, 10);

        $this->actingAs($this->adminGudang);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('terima')
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('diterima', $retur->status);
        $this->assertNotNull($retur->tanggal_diterima);

        $this->assertSame(20, (int) $batch->fresh()->jumlah);

        $stokGudang = StokGudang::where('obat_id', $this->obat->id)->first();
        $this->assertNotNull($stokGudang);
        $this->assertSame(10, (int) $stokGudang->jumlah);
    }

    public function test_tandai_selesai_action_mengubah_status_ke_selesai(): void
    {
        $retur = $this->makeRetur('puskesmas_ke_gudang', 'diterima', $this->puskesmasA->id, null);

        $this->actingAs($this->userPuskesmasA);

        Livewire::test(ViewReturObat::class, ['record' => $retur->id])
            ->callAction('tandai_selesai')
            ->assertHasNoActionErrors();

        $retur->refresh();
        $this->assertSame('selesai', $retur->status);
    }
}
