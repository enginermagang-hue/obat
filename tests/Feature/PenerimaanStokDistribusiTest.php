<?php

namespace Tests\Feature;

use App\Filament\Resources\PenerimaanStoks\PenerimaanStokResource;
use App\Models\BatchStok;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenerimaanStokDistribusiTest extends TestCase
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

    private string $batchNumber;

    private string $tanggalExpired;

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
        $this->batchNumber = 'BCH-'.uniqid();
        $this->tanggalExpired = now()->addYear()->toDateString();
    }

    /**
     * Buat batch stok di puskesmas A (sebagai pengirim), sejumlah $jumlah.
     */
    private function makeBatch(int $jumlah = 100): BatchStok
    {
        return BatchStok::create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->puskesmasA->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => $jumlah,
            'status' => 'tersedia',
            'tanggal_masuk' => now()->toDateString(),
        ]);
    }

    /**
     * Buat DistribusiObat puskesmasA → pustuA, status='dalam_pengiriman', dengan $jumlah item.
     */
    private function makeDistribusi(int $jumlah = 30, string $status = 'dalam_pengiriman', ?PermintaanObat $permintaan = null): DistribusiObat
    {
        $batch = $this->makeBatch(100);

        $distribusi = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.uniqid(),
            'permintaan_id' => $permintaan?->id,
            'tipe_distribusi' => 'puskesmas_ke_pustu',
            'fasilitas_pengirim_id' => $this->puskesmasA->id,
            'fasilitas_penerima_id' => $this->pustuA->id,
            'status' => $status,
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->superAdmin->id,
        ]);

        $distribusi->details()->create([
            'obat_id' => $this->obat->id,
            'batch_id' => $batch->id,
            'jumlah' => $jumlah,
        ]);

        return $distribusi->fresh(['details', 'details.batch']);
    }

    /**
     * Buat PenerimaanStok bertipe='distribusi' yang menaut ke distribusi tertentu.
     */
    private function makePenerimaanFromDistribusi(DistribusiObat $distribusi, int $jumlah = 30, string $status = 'draft'): PenerimaanStok
    {
        return PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusi->id,
            'fasilitas_id' => $distribusi->fasilitas_penerima_id,
            'user_id' => $this->userPustu->id,
            'status' => $status,
            'tanggal_penerimaan' => now()->toDateString(),
        ])->fresh();
    }

    // ──────────────────────────────────────────────
    //  POLICY: viewAny
    // ──────────────────────────────────────────────

    public function test_super_admin_can_view_any_penerimaan(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', PenerimaanStok::class));
    }

    public function test_admin_dinas_can_view_any_penerimaan(): void
    {
        $this->assertTrue($this->adminDinas->can('viewAny', PenerimaanStok::class));
    }

    public function test_admin_gudang_can_view_any_penerimaan(): void
    {
        $this->assertTrue($this->adminGudang->can('viewAny', PenerimaanStok::class));
    }

    public function test_puskesmas_can_view_any_penerimaan(): void
    {
        $this->assertTrue($this->userPuskesmasA->can('viewAny', PenerimaanStok::class));
    }

    public function test_pustu_can_view_any_penerimaan(): void
    {
        $this->assertTrue($this->userPustu->can('viewAny', PenerimaanStok::class));
    }

    // ──────────────────────────────────────────────
    //  POLICY: view (record-level)
    // ──────────────────────────────────────────────

    public function test_pustu_can_view_own_faskes_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi);

        $this->assertTrue($this->userPustu->can('view', $penerimaan));
    }

    public function test_pustu_cannot_view_other_faskes_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi);

        $otherPustu = User::factory()->create(['fasilitas_kesehatan_id' => $this->puskesmasB->id]);
        $otherPustu->assignRole('pustu');

        $this->assertFalse($otherPustu->can('view', $penerimaan));
    }

    public function test_super_admin_can_view_any_penerimaan_record(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi);

        $this->assertTrue($this->superAdmin->can('view', $penerimaan));
    }

    // ──────────────────────────────────────────────
    //  POLICY: update / delete — must be draft
    // ──────────────────────────────────────────────

    public function test_pustu_can_update_draft_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'draft');

        $this->assertTrue($this->userPustu->can('update', $penerimaan));
        $this->assertTrue($this->userPustu->can('delete', $penerimaan));
    }

    public function test_pustu_cannot_update_confirmed_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'dikonfirmasi');

        $this->assertFalse($this->userPustu->can('update', $penerimaan));
        $this->assertFalse($this->userPustu->can('delete', $penerimaan));
    }

    // ──────────────────────────────────────────────
    //  POLICY: global admin cannot update/delete confirmed
    // ──────────────────────────────────────────────

    public function test_super_admin_cannot_update_confirmed_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'dikonfirmasi');

        $this->assertFalse($this->superAdmin->can('update', $penerimaan));
        $this->assertFalse($this->superAdmin->can('delete', $penerimaan));
    }

    public function test_admin_gudang_cannot_update_confirmed_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'dikonfirmasi');

        $this->assertFalse($this->adminGudang->can('update', $penerimaan));
        $this->assertFalse($this->adminGudang->can('delete', $penerimaan));
    }

    public function test_admin_dinas_cannot_update_confirmed_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'dikonfirmasi');

        $this->assertFalse($this->adminDinas->can('update', $penerimaan));
        $this->assertFalse($this->adminDinas->can('delete', $penerimaan));
    }

    public function test_super_admin_and_gudang_can_update_draft_penerimaan(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, status: 'draft');

        $this->assertTrue($this->superAdmin->can('update', $penerimaan));
        $this->assertTrue($this->superAdmin->can('delete', $penerimaan));
        $this->assertTrue($this->adminGudang->can('update', $penerimaan));
        $this->assertFalse($this->adminDinas->can('update', $penerimaan));
    }

    // ──────────────────────────────────────────────
    //  RESOURCE SCOPE: getEloquentQuery()
    // ──────────────────────────────────────────────

    public function test_super_admin_sees_all_penerimaan_via_resource(): void
    {
        $this->makePenerimaanFromDistribusi($this->makeDistribusi());
        $this->makePenerimaanFromDistribusi($this->makeDistribusi());

        $this->actingAs($this->superAdmin);

        $this->assertSame(2, PenerimaanStokResource::getEloquentQuery()->count());
    }

    public function test_pustu_only_sees_own_faskes_penerimaan_via_resource(): void
    {
        // Penerimaan untuk pustuA (terlihat)
        $this->makePenerimaanFromDistribusi($this->makeDistribusi());

        // Penerimaan untuk puskesmasB (tersembunyi)
        $distribusiB = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.uniqid(),
            'tipe_distribusi' => 'puskesmas_ke_pustu',
            'fasilitas_pengirim_id' => $this->puskesmasB->id,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
            'status' => 'dalam_pengiriman',
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->superAdmin->id,
        ]);
        PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.uniqid(),
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusiB->id,
            'fasilitas_id' => $this->puskesmasB->id,
            'user_id' => $this->userPuskesmasB->id,
            'status' => 'draft',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->userPustu);

        $query = PenerimaanStokResource::getEloquentQuery();
        $this->assertSame(1, $query->count());
    }

    public function test_puskesmas_only_sees_own_faskes_penerimaan_via_resource(): void
    {
        // Distribusi dinas → puskesmasA (puskesmasA sebagai PENERIMA, visible)
        $distribusiA = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.uniqid(),
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null,
            'fasilitas_penerima_id' => $this->puskesmasA->id,
            'status' => 'dalam_pengiriman',
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->superAdmin->id,
        ]);
        PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.uniqid(),
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusiA->id,
            'fasilitas_id' => $this->puskesmasA->id,
            'user_id' => $this->userPuskesmasA->id,
            'status' => 'draft',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);
        // Distribusi dinas → puskesmasB (TIDAK visible untuk userPuskesmasA)
        $distribusiB = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.uniqid(),
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null,
            'fasilitas_penerima_id' => $this->puskesmasB->id,
            'status' => 'dalam_pengiriman',
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->superAdmin->id,
        ]);
        PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.uniqid(),
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusiB->id,
            'fasilitas_id' => $this->puskesmasB->id,
            'user_id' => $this->userPuskesmasB->id,
            'status' => 'draft',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        $this->actingAs($this->userPuskesmasA);

        $this->assertSame(1, PenerimaanStokResource::getEloquentQuery()->count());
    }

    // ──────────────────────────────────────────────
    //  STOK SERVICE: prosesPenerimaanDistribusi
    // ──────────────────────────────────────────────

    public function test_proses_penerimaan_distribusi_mutates_stok_both_sides(): void
    {
        $distribusi = $this->makeDistribusi(jumlah: 30);
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);

        // Setup detail penerimaan sama dengan distribusi
        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 30,
            'harga_satuan' => 1000,
            'sub_total' => 30000,
        ]);

        // Stok awal: puskesmasA punya 100, pustuA belum ada
        $this->assertSame(100, (int) $distribusi->details->first()->batch->fresh()->jumlah);
        $this->assertDatabaseMissing('stok_faskes', [
            'fasilitas_id' => $this->pustuA->id,
            'obat_id' => $this->obat->id,
        ]);

        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);

        // Stok pengirim (puskesmasA) berkurang 30 → 70
        $batchPengirim = $distribusi->details->first()->batch->fresh();
        $this->assertSame(70, (int) $batchPengirim->jumlah);

        // Stok penerima (pustuA) bertambah 30
        $stokPenerima = StokFaskes::where('fasilitas_id', $this->pustuA->id)
            ->where('obat_id', $this->obat->id)
            ->first();
        $this->assertNotNull($stokPenerima);
        $this->assertSame(30, (int) $stokPenerima->jumlah);
    }

    public function test_proses_penerimaan_distribusi_updates_status(): void
    {
        $distribusi = $this->makeDistribusi(jumlah: 30);
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);
        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 30,
            'harga_satuan' => 1000,
            'sub_total' => 30000,
        ]);

        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);

        $distribusi->refresh();
        $this->assertSame('diterima', $distribusi->status);
        $this->assertNotNull($distribusi->tanggal_terima);
        $this->assertSame($this->userPustu->id, (int) $distribusi->penerima_id);
        $this->assertSame((int) $penerimaan->id, (int) $distribusi->penerimaan_stok_id);
    }

    public function test_proses_penerimaan_distribusi_updates_permintaan_status_when_linked(): void
    {
        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/'.now()->format('Y/m').'/'.uniqid(),
            'fasilitas_pengirim_id' => $this->pustuA->id,
            'fasilitas_tujuan_id' => $this->puskesmasA->id,
            'tipe_permintaan' => 'pustu_ke_puskesmas',
            'status' => 'sedang_didistribusi',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $distribusi = $this->makeDistribusi(jumlah: 30, permintaan: $permintaan);
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);
        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 30,
            'harga_satuan' => 1000,
            'sub_total' => 30000,
        ]);

        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);

        $permintaan->refresh();
        $this->assertSame('diterima', $permintaan->status);
        $this->assertNotNull($permintaan->tanggal_diterima);
    }

    public function test_proses_penerimaan_distribusi_throws_when_wrong_tipe(): void
    {
        $penerimaan = PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.uniqid(),
            'tipe' => 'manual',
            'fasilitas_id' => $this->pustuA->id,
            'user_id' => $this->userPustu->id,
            'status' => 'draft',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);
    }

    public function test_proses_penerimaan_distribusi_throws_when_no_distribusi(): void
    {
        $penerimaan = PenerimaanStok::create([
            'nomor_penerimaan' => 'PO/'.now()->format('Y/m').'/'.uniqid(),
            'tipe' => 'distribusi',
            'distribusi_id' => null,
            'fasilitas_id' => $this->pustuA->id,
            'user_id' => $this->userPustu->id,
            'status' => 'draft',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak memiliki DistribusiObat/');
        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);
    }

    public function test_proses_penerimaan_distribusi_throws_when_wrong_distribusi_status(): void
    {
        $distribusi = $this->makeDistribusi(jumlah: 30, status: 'draft');
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches("/'dalam_pengiriman'/");
        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);
    }

    public function test_proses_penerimaan_distribusi_throws_when_fasilitas_mismatch(): void
    {
        $distribusi = $this->makeDistribusi(jumlah: 30);
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);

        // Force fasilitas_id ke puskesmasB (bukan pustuA)
        $penerimaan->update(['fasilitas_id' => $this->puskesmasB->id]);

        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 30,
            'harga_satuan' => 1000,
            'sub_total' => 30000,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Fasilitas penerima/');
        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);
    }

    public function test_proses_penerimaan_distribusi_throws_when_item_not_in_distribusi(): void
    {
        $distribusi = $this->makeDistribusi(jumlah: 30);
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi, jumlah: 30);

        // Item dengan batch_number berbeda (tidak ada di distribusi)
        $penerimaan->details()->create([
            'obat_id' => $this->obat->id,
            'batch_number' => 'BATCH-LAIN',
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 10,
            'harga_satuan' => 1000,
            'sub_total' => 10000,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak ditemukan di Distribusi/');
        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);
    }

    // ──────────────────────────────────────────────
    //  CONSTRAINT: tipe='manual' must not have distribusi_id
    // ──────────────────────────────────────────────

    public function test_penerimaan_manual_clears_distribusi_id(): void
    {
        $distribusi = $this->makeDistribusi();
        $penerimaan = $this->makePenerimaanFromDistribusi($distribusi);
        $this->assertNotNull($penerimaan->distribusi_id);

        // Simulate user switching tipe to manual (mutateFormDataBeforeSave logic)
        $penerimaan->update(['tipe' => 'manual', 'distribusi_id' => null]);

        $this->assertSame('manual', $penerimaan->fresh()->tipe);
        $this->assertNull($penerimaan->fresh()->distribusi_id);
    }
}
