<?php

namespace Tests\Feature;

use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailPermintaanObat;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\FefoService;
use App\Services\StokService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end alur permintaan → distribusi → penerimaan untuk tipe
 * puskesmas_ke_dinas:
 *
 *   1. Puskesmas membuat PermintaanObat (tipe puskesmas_ke_dinas)
 *   2. Admin Dinas menyetujui permintaan
 *   3. Admin Gudang (gudang dinas) membuat DistribusiObat dari permintaan
 *   4. Puskesmas menerima distribusi (PenerimaanStok tipe='distribusi')
 *
 * Test ini menggunakan Eloquent langsung, bukan Livewire Filament, untuk
 * konsistensi dengan PenerimaanStokDistribusiTest. Setiap step mereplikasi
 * apa yang dilakukan CreatePermintaanObat, CreateDistribusiObat, dan
 * CreatePenerimaanStok.
 */
class AlurPermintaanDistribusiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminDinas;

    private User $adminGudang;

    private User $userPuskesmas;

    private FasilitasKesehatan $puskesmas;

    private FasilitasKesehatan $pustu;

    private Obat $obat;

    private string $batchNumber;

    private string $tanggalExpired;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Fasilitas: puskesmas dengan satu pustu di bawahnya
        $this->puskesmas = FasilitasKesehatan::create([
            'kode_faskes' => 'PKM-001',
            'nama' => 'Puskesmas Test',
            'tipe' => 'puskesmas',
            'alamat' => 'Jl. Puskesmas',
            'pic' => 'Test PIC',
            'kontak_pic' => '123',
            'status' => 'aktif',
        ]);

        $this->pustu = FasilitasKesehatan::create([
            'kode_faskes' => 'PST-001',
            'nama' => 'Pustu Test',
            'tipe' => 'pustu',
            'puskesmas_induk_id' => $this->puskesmas->id,
            'alamat' => 'Jl. Pustu',
            'pic' => 'Test PIC Pustu',
            'kontak_pic' => '1234',
            'status' => 'aktif',
        ]);

        // Users
        $this->userPuskesmas = User::factory()->create([
            'fasilitas_kesehatan_id' => $this->puskesmas->id,
        ]);
        $this->userPuskesmas->assignRole('puskesmas');

        $this->adminDinas = User::factory()->create([
            'fasilitas_kesehatan_id' => null,
        ]);
        $this->adminDinas->assignRole('admin_dinas');

        $this->adminGudang = User::factory()->create([
            'fasilitas_kesehatan_id' => null,
        ]);
        $this->adminGudang->assignRole('admin_gudang');

        // Obat + batch stok di gudang (fasilitas_id = null)
        $this->obat = Obat::factory()->create();
        $this->batchNumber = 'BCH-'.uniqid();
        $this->tanggalExpired = now()->addYear()->toDateString();

        BatchStok::create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => null, // gudang dinas
            'batch_number' => $this->batchNumber,
            'tanggal_expired' => $this->tanggalExpired,
            'jumlah' => 100,
            'status' => 'tersedia',
            'tanggal_masuk' => now()->toDateString(),
        ]);
    }

    /**
     * Step 1: Puskesmas membuat permintaan (tipe puskesmas_ke_dinas).
     * Logika ini mereplikasi CreatePermintaanObat::mutateFormDataBeforeCreate
     * untuk user puskesmas + klik "Kirim" (status = menunggu_persetujuan).
     */
    private function makePermintaan(int $jumlahDiminta = 30): PermintaanObat
    {
        $permintaan = PermintaanObat::create([
            'fasilitas_pengirim_id' => $this->puskesmas->id,
            'fasilitas_tujuan_id' => null, // dinas (tidak punya faskes)
            'tipe_permintaan' => 'puskesmas_ke_dinas',
            'status' => 'menunggu_persetujuan',
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        $permintaan->details()->create([
            'obat_id' => $this->obat->id,
            'jumlah_diminta' => $jumlahDiminta,
        ]);

        return $permintaan->fresh(['details']);
    }

    /**
     * Step 2: Admin dinas menyetujui permintaan.
     */
    private function approvePermintaan(PermintaanObat $permintaan): void
    {
        $permintaan->update([
            'status' => 'disetujui',
            'disetujui_oleh' => $this->adminDinas->id,
            'tanggal_disetujui' => now()->toDateString(),
        ]);
    }

    /**
     * Step 3: Admin gudang membuat & mengirim distribusi dari permintaan.
     * Logika ini mereplikasi CreateDistribusiObat dengan permintaan_id
     * pre-fill, mutateFormDataBeforeCreate (admin_gudang → null pengirim,
     * dinas_ke_puskesmas), dan handleRecordCreation (kirim + update
     * permintaan ke sedang_didistribusi + update jumlah_dikirim).
     */
    private function makeDistribusi(PermintaanObat $permintaan, int $jumlah = 30): DistribusiObat
    {
        // FEFO allocation dari gudang (fasilitas_id = null)
        $allocation = app(FefoService::class)->allocate($this->obat->id, $jumlah, null);

        $distribusi = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.now()->format('Y').'/'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'permintaan_id' => $permintaan->id,
            'tipe_distribusi' => 'dinas_ke_puskesmas',
            'fasilitas_pengirim_id' => null, // gudang dinas
            'fasilitas_penerima_id' => $permintaan->fasilitas_pengirim_id, // = puskesmas
            'status' => 'dalam_pengiriman',
            'tanggal_kirim' => now()->toDateString(),
            'pengirim_id' => $this->adminGudang->id,
        ]);

        foreach ($allocation as $alloc) {
            DetailDistribusiObat::create([
                'distribusi_id' => $distribusi->id,
                'obat_id' => $this->obat->id,
                'batch_id' => $alloc['batch_id'],
                'jumlah' => $alloc['jumlah'],
            ]);
        }

        // Update permintaan ke sedang_didistribusi
        $permintaan->update(['status' => 'sedang_didistribusi']);

        // Update jumlah_dikirim (replikasi updateJumlahDikirim di CreateDistribusiObat)
        DetailPermintaanObat::where('permintaan_id', $permintaan->id)
            ->where('obat_id', $this->obat->id)
            ->update(['jumlah_dikirim' => $jumlah]);

        return $distribusi->fresh(['details', 'details.batch']);
    }

    /**
     * Step 4: Puskesmas membuat & mengonfirmasi penerimaan dari distribusi.
     * Logika ini mereplikasi CreatePenerimaanStok dengan URL pre-fill
     * (tipe=distribusi, distribusi_id=X), loadDistribusiItems,
     * mutateFormDataBeforeCreate, dan afterCreate (dikonfirmasi → StokService).
     */
    private function makePenerimaan(DistribusiObat $distribusi): PenerimaanStok
    {
        $penerimaan = PenerimaanStok::create([
            'tipe' => 'distribusi',
            'distribusi_id' => $distribusi->id,
            'fasilitas_id' => $distribusi->fasilitas_penerima_id, // = puskesmas
            'user_id' => $this->userPuskesmas->id,
            'status' => 'dikonfirmasi',
            'tanggal_penerimaan' => now()->toDateString(),
        ]);

        foreach ($distribusi->details as $distDetail) {
            $penerimaan->details()->create([
                'obat_id' => $distDetail->obat_id,
                'batch_number' => $distDetail->batch->batch_number,
                'tanggal_expired' => $distDetail->batch->tanggal_expired,
                'jumlah' => $distDetail->jumlah,
            ]);
        }

        app(StokService::class)->prosesPenerimaanDistribusi($penerimaan);

        return $penerimaan->fresh();
    }

    // ──────────────────────────────────────────────
    //  HAPPY PATH: full E2E flow
    // ──────────────────────────────────────────────

    public function test_full_flow_puskesmas_permintaan_through_penerimaan(): void
    {
        // Step 1: Puskesmas membuat permintaan
        $permintaan = $this->makePermintaan(jumlahDiminta: 30);
        $this->assertSame('menunggu_persetujuan', $permintaan->status);
        $this->assertSame('puskesmas_ke_dinas', $permintaan->tipe_permintaan);
        $this->assertSame($this->puskesmas->id, (int) $permintaan->fasilitas_pengirim_id);
        $this->assertNull($permintaan->fasilitas_tujuan_id);

        // Step 2: Admin dinas menyetujui
        $this->approvePermintaan($permintaan);
        $permintaan->refresh();
        $this->assertSame('disetujui', $permintaan->status);
        $this->assertSame($this->adminDinas->id, (int) $permintaan->disetujui_oleh);
        $this->assertNotNull($permintaan->tanggal_disetujui);

        // Step 3: Admin gudang membuat & mengirim distribusi
        $distribusi = $this->makeDistribusi($permintaan, jumlah: 30);
        $this->assertSame('dalam_pengiriman', $distribusi->status);
        $this->assertSame('dinas_ke_puskesmas', $distribusi->tipe_distribusi);
        $this->assertNull($distribusi->fasilitas_pengirim_id);
        $this->assertSame($this->puskesmas->id, (int) $distribusi->fasilitas_penerima_id);
        $this->assertCount(1, $distribusi->details);
        $this->assertSame(30, (int) $distribusi->details->first()->jumlah);

        $permintaan->refresh();
        $this->assertSame('sedang_didistribusi', $permintaan->status);

        // Step 4: Puskesmas menerima
        $penerimaan = $this->makePenerimaan($distribusi);
        $this->assertSame('dikonfirmasi', $penerimaan->status);
        $this->assertSame('distribusi', $penerimaan->tipe);

        // Final state
        $this->assertSame('diterima', $permintaan->fresh()->status);
        $this->assertSame('diterima', $distribusi->fresh()->status);
        $this->assertNotNull($distribusi->fresh()->tanggal_terima);
        $this->assertSame($this->userPuskesmas->id, (int) $distribusi->fresh()->penerima_id);
    }

    // ──────────────────────────────────────────────
    //  STATUS TRANSITIONS
    // ──────────────────────────────────────────────

    public function test_permintaan_status_transitions_through_flow(): void
    {
        $permintaan = $this->makePermintaan();
        $this->assertSame('menunggu_persetujuan', $permintaan->status);

        $this->approvePermintaan($permintaan);
        $this->assertSame('disetujui', $permintaan->fresh()->status);

        $this->makeDistribusi($permintaan);
        $this->assertSame('sedang_didistribusi', $permintaan->fresh()->status);

        // Finish the flow to test the last transition
        $distribusi = DistribusiObat::where('permintaan_id', $permintaan->id)->first();
        $this->makePenerimaan($distribusi);
        $this->assertSame('diterima', $permintaan->fresh()->status);
    }

    public function test_distribusi_status_transitions_through_flow(): void
    {
        $permintaan = $this->makePermintaan();
        $this->approvePermintaan($permintaan);

        $distribusi = $this->makeDistribusi($permintaan);
        $this->assertSame('dalam_pengiriman', $distribusi->status);

        $this->makePenerimaan($distribusi);
        $this->assertSame('diterima', $distribusi->fresh()->status);
    }

    // ──────────────────────────────────────────────
    //  BIDIRECTIONAL FK LINK
    // ──────────────────────────────────────────────

    public function test_bidirectional_link_between_distribusi_and_penerimaan(): void
    {
        $permintaan = $this->makePermintaan();
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan);
        $penerimaan = $this->makePenerimaan($distribusi);

        $this->assertSame($distribusi->id, (int) $penerimaan->distribusi_id);
        $this->assertSame($penerimaan->id, (int) $distribusi->fresh()->penerimaan_stok_id);

        // Reverse: distribusi relates back to the permintaan
        $this->assertSame($permintaan->id, (int) $distribusi->permintaan_id);
    }

    // ──────────────────────────────────────────────
    //  STOCK MUTATION: gudang → puskesmas
    // ──────────────────────────────────────────────

    public function test_stock_mutation_gudang_to_puskesmas(): void
    {
        // Stok awal: gudang punya 100, puskesmas belum ada
        $batchGudangAwal = BatchStok::where('obat_id', $this->obat->id)
            ->whereNull('fasilitas_id')
            ->first();
        $this->assertSame(100, (int) $batchGudangAwal->jumlah);
        $this->assertDatabaseMissing('stok_faskes', [
            'fasilitas_id' => $this->puskesmas->id,
            'obat_id' => $this->obat->id,
        ]);

        // Run full flow
        $permintaan = $this->makePermintaan(jumlahDiminta: 30);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, jumlah: 30);
        $this->makePenerimaan($distribusi);

        // After: gudang 100 - 30 = 70, puskesmas +30
        $batchGudangAkhir = $batchGudangAwal->fresh();
        $this->assertSame(70, (int) $batchGudangAkhir->jumlah);

        $stokPuskesmas = StokFaskes::where('fasilitas_id', $this->puskesmas->id)
            ->where('obat_id', $this->obat->id)
            ->first();
        $this->assertNotNull($stokPuskesmas);
        $this->assertSame(30, (int) $stokPuskesmas->jumlah);

        // StokGudang aggregate juga harus sinkron (di-decrement oleh kurangiStokPengirimDistribusi)
        $stokGudang = StokGudang::where('obat_id', $this->obat->id)->first();
        $this->assertSame(70, (int) $stokGudang->jumlah);
    }

    // ──────────────────────────────────────────────
    //  DETAIL PERMINTAAN: jumlah_dikirim updated
    // ──────────────────────────────────────────────

    public function test_detail_permintaan_jumlah_dikirim_updated_on_distribusi(): void
    {
        $permintaan = $this->makePermintaan(jumlahDiminta: 30);
        $this->approvePermintaan($permintaan);

        // Before distribusi: jumlah_dikirim null
        $this->assertNull($permintaan->details->first()->jumlah_dikirim);

        $this->makeDistribusi($permintaan, jumlah: 30);

        // After: jumlah_dikirim = 30
        $this->assertSame(30, (int) $permintaan->fresh()->details->first()->jumlah_dikirim);
        $this->assertSame(30, (int) $permintaan->fresh()->details->first()->jumlah_diminta);
    }

    // ──────────────────────────────────────────────
    //  DETAIL PENERIMAAN matches DETAIL DISTRIBUSI
    // ──────────────────────────────────────────────

    public function test_penerimaan_detail_matches_distribusi_detail(): void
    {
        $permintaan = $this->makePermintaan(jumlahDiminta: 30);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, jumlah: 30);
        $penerimaan = $this->makePenerimaan($distribusi);

        $penerimaanDetails = $penerimaan->details;
        $distribusiDetails = $distribusi->details;

        $this->assertCount($distribusiDetails->count(), $penerimaanDetails);

        foreach ($distribusiDetails as $i => $distDetail) {
            $penDetail = $penerimaanDetails[$i];

            $this->assertSame($distDetail->obat_id, $penDetail->obat_id);
            $this->assertSame($distDetail->batch->batch_number, $penDetail->batch_number);
            $this->assertSame(
                $distDetail->batch->tanggal_expired->toDateString(),
                $penDetail->tanggal_expired->toDateString(),
            );
            $this->assertSame($distDetail->jumlah, (int) $penDetail->jumlah);
        }
    }

    // ──────────────────────────────────────────────
    //  PERMINTAAN: jumlah_diminta preserved through flow
    // ──────────────────────────────────────────────

    public function test_permintaan_detail_jumlah_diminta_unchanged(): void
    {
        $permintaan = $this->makePermintaan(jumlahDiminta: 30);
        $this->approvePermintaan($permintaan);
        $distribusi = $this->makeDistribusi($permintaan, jumlah: 30);
        $this->makePenerimaan($distribusi);

        // jumlah_diminta tetap 30, jumlah_dikirim = 30, jumlah_diterima = 30
        $detail = $permintaan->fresh()->details->first();
        $this->assertSame(30, (int) $detail->jumlah_diminta);
        $this->assertSame(30, (int) $detail->jumlah_dikirim);
    }
}
