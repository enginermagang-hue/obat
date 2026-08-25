<?php

namespace Tests\Browser;

use App\Models\BatchStok;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\RiwayatStok;
use App\Models\StokGudang;
use App\Models\SumberDana;
use App\Models\SumberDanaPenggunaan;
use App\Models\Supplier;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\FillsFilamentFields;
use Tests\DuskTestCase;

class PenerimaanStokDuskTest extends DuskTestCase
{
    use FillsFilamentFields;

    private const NOMOR = 'DUSK/TEST/001';

    private const JUMLAH = 100;

    private const HARGA = 1500;

    private User $user;

    private Supplier $supplier;

    private SumberDana $sumberDana;

    private Obat $obat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true]);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin_gudang');

        $this->supplier = Supplier::factory()->create([
            'nama' => 'PT Dusk Supplier Uji',
            'status' => 'aktif',
        ]);

        $this->sumberDana = SumberDana::factory()->create([
            'nama' => 'Dana Uji Coba Dusk 2026',
        ]);

        $this->obat = Obat::factory()->create([
            'nama_obat' => 'Parasetamol Uji Dusk Tablet 500 mg',
            'harga_satuan' => self::HARGA,
        ]);
    }

    /**
     * Alur lengkap: wizard 3 langkah, klik Buat (status dikonfirmasi),
     * stok masuk lewat StokService (batch, stok gudang, riwayat, realisasi dana).
     */
    public function test_alur_lengkap_penerimaan_pembelian_dikonfirmasi(): void
    {
        $expired = now()->addYear()->toDateString();

        $this->browse(function (Browser $browser) use ($expired) {
            $browser->loginAs($this->user)
                ->visit('/admin/penerimaan-stok/create')
                ->assertPathIs('/admin/penerimaan-stok/create');

            // Langkah 1: Informasi
            $browser->type('[id$="nomor_penerimaan"]', self::NOMOR);
            $this->isiField($browser, '[id$="tipe"]', 'pembelian');
            $browser->waitFor('[id$="supplier_id"]')
                ->pause(1200);
            $this->isiField($browser, '[id$="tanggal_penerimaan"]', now()->toDateString());
            $browser->type('[id$="nomor_po"]', 'PO/DUSK/001')
                ->type('[id$="nomor_invoice"]', 'INV/DUSK/001');
            $this->isiField($browser, '[id$="supplier_id"]', (string) $this->supplier->getKey());
            $this->isiField($browser, '[id$="sumber_dana_id"]', (string) $this->sumberDana->getKey());
            $browser->pause(800);

            $browser->press('Selanjutnya')
                ->pause(2500);

            // Langkah 2: Item Obat
            $this->isiField($browser, '[id$="obat_id"]', (string) $this->obat->getKey());
            $this->isiField($browser, 'input[id$=".jumlah"]', (string) self::JUMLAH);
            $this->isiField($browser, 'input[id$=".tanggal_expired"]', $expired);
            $this->isiField($browser, 'input[id$=".batch_number"]', 'BATCH-DUSK-001');
            $this->isiField($browser, 'input[id$=".harga_satuan"]', (string) self::HARGA);
            $browser->pause(1500)
                ->press('Selanjutnya');

            // Langkah 3: Konfirmasi - ringkasan menampilkan item & total
            $browser->waitForText($this->obat->nama_obat, 10)
                ->waitUsing(10, 300, fn () => $browser->assertSee('Rp 150.000,00'))
                ->press('Buat')
                ->waitUsing(15, 250, fn () => $browser->assertPathBeginsWith('/admin/penerimaan-stok/'))
                ->waitUsing(10, 300, fn () => $browser->assertSee(self::NOMOR));
            $browser->waitForText('Penerimaan stok berhasil dibuat', 10);
        });

        $penerimaan = PenerimaanStok::query()
            ->where('nomor_penerimaan', self::NOMOR)
            ->firstOrFail();
        $this->assertSame('dikonfirmasi', $penerimaan->status);
        $this->assertSame('pembelian', $penerimaan->tipe);
        $this->assertEquals(self::JUMLAH * self::HARGA, (float) $penerimaan->total_biaya);

        $detail = $penerimaan->details()->firstOrFail();
        $this->assertSame($this->obat->getKey(), $detail->obat_id);
        $this->assertSame(self::JUMLAH, $detail->jumlah);
        $this->assertEquals(self::HARGA, (float) $detail->harga_satuan);
        $this->assertEquals(self::JUMLAH * self::HARGA, (float) $detail->sub_total);

        $batch = BatchStok::query()
            ->where('penerimaan_id', $penerimaan->getKey())
            ->where('obat_id', $this->obat->getKey())
            ->firstOrFail();
        $this->assertSame('BATCH-DUSK-001', $batch->batch_number);
        $this->assertSame(self::JUMLAH, $batch->jumlah);

        $stokGudang = StokGudang::query()
            ->where('obat_id', $this->obat->getKey())
            ->firstOrFail();
        $this->assertSame(self::JUMLAH, $stokGudang->jumlah);

        $riwayat = RiwayatStok::query()
            ->where('tipe', 'masuk')
            ->where('referensi_type', $penerimaan::class)
            ->where('referensi_id', $penerimaan->getKey())
            ->firstOrFail();
        $this->assertSame(self::JUMLAH, $riwayat->jumlah);

        $this->assertTrue(
            SumberDanaPenggunaan::query()
                ->where('sumber_dana_id', $this->sumberDana->getKey())
                ->where('tipe', 'realisasi')
                ->exists(),
        );
    }

    /**
     * Klik Simpan pada langkah Konfirmasi: tersimpan sebagai draft,
     * tanpa gerakan stok (batch & riwayat tidak tercipta).
     */
    public function test_simpan_penerimaan_sebagai_draft(): void
    {
        $expired = now()->addYear()->toDateString();

        $this->browse(function (Browser $browser) use ($expired) {
            $browser->loginAs($this->user)
                ->visit('/admin/penerimaan-stok/create')
                ->assertPathIs('/admin/penerimaan-stok/create');

            // Langkah 1: Informasi
            $browser->type('[id$="nomor_penerimaan"]', self::NOMOR);
            $this->isiField($browser, '[id$="tipe"]', 'pembelian');
            $browser->waitFor('[id$="supplier_id"]')
                ->pause(1200);
            $this->isiField($browser, '[id$="tanggal_penerimaan"]', now()->toDateString());
            $this->isiField($browser, '[id$="supplier_id"]', (string) $this->supplier->getKey());
            $browser->pause(800);

            $browser->press('Selanjutnya')
                ->pause(2500);

            // Langkah 2: Item Obat
            $this->isiField($browser, '[id$="obat_id"]', (string) $this->obat->getKey());
            $this->isiField($browser, 'input[id$=".jumlah"]', (string) self::JUMLAH);
            $this->isiField($browser, 'input[id$=".tanggal_expired"]', $expired);
            $this->isiField($browser, 'input[id$=".batch_number"]', 'BATCH-DUSK-DRAFT');
            $this->isiField($browser, 'input[id$=".harga_satuan"]', (string) self::HARGA);
            $browser->pause(1500)
                ->press('Selanjutnya');

            // Langkah 3: Konfirmasi - simpan sebagai draft
            $browser->waitUsing(15, 300, fn () => $browser->assertSee('Rp 150.000,00'))
                ->press('Simpan')
                ->waitUsing(15, 250, fn () => $browser->assertPathBeginsWith('/admin/penerimaan-stok/'))
                ->waitUsing(10, 300, fn () => $browser->assertSee(self::NOMOR));
            $browser->waitForText('Penerimaan stok disimpan sebagai draft', 10);
        });

        $penerimaan = PenerimaanStok::query()
            ->where('nomor_penerimaan', self::NOMOR)
            ->firstOrFail();
        $this->assertSame('draft', $penerimaan->status);
        $this->assertSame(1, $penerimaan->details()->count());

        $this->assertSame(0, BatchStok::query()->where('penerimaan_id', $penerimaan->getKey())->count());

        $this->assertFalse(
            RiwayatStok::query()
                ->where('referensi_type', $penerimaan::class)
                ->where('referensi_id', $penerimaan->getKey())
                ->exists(),
        );
    }

    /**
     * Validasi langkah pertama: Selanjutnya dengan form kosong memunculkan
     * pesan required dan tetap berada di halaman create.
     */
    public function test_validasi_langkah_informasi_form_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/penerimaan-stok/create')
                ->assertPathIs('/admin/penerimaan-stok/create')
                ->press('Selanjutnya')
                ->waitUsing(5, 200, fn () => $browser->assertSee('field is required'))
                ->assertPathIs('/admin/penerimaan-stok/create')
                ->assertVisible('[id$="nomor_penerimaan"]');
        });
    }
}
