<?php

namespace Tests\Feature;

use App\Models\BatchStok;
use App\Models\DetailNeracaSumberDana;
use App\Models\DetailNeracaTahunan;
use App\Models\NeracaTahunan;
use App\Models\Obat;
use App\Models\PenerimaanStok;
use App\Models\RiwayatStok;
use App\Models\SumberDana;
use App\Services\NeracaTahunanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeracaTahunanSumberDanaTest extends TestCase
{
    use RefreshDatabase;

    private NeracaTahunanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NeracaTahunanService::class);
    }

    public function test_generate_mengisi_baris_sumber_dana_fallback_tanpa_sd(): void
    {
        $tahun = (int) date('Y');
        $obat = Obat::factory()->create(['harga_satuan' => 1000]);
        $neraca = NeracaTahunan::factory()->create(['tahun' => $tahun, 'fasilitas_id' => null]);

        RiwayatStok::factory()->create([
            'obat_id' => $obat->id,
            'fasilitas_id' => null,
            'jumlah' => 100,
            'stok_sebelum' => 0,
            'stok_sesudah' => 100,
            'tanggal' => "{$tahun}-06-15",
        ]);

        $this->service->generate($neraca);

        $detail = DetailNeracaTahunan::query()
            ->where('neraca_id', $neraca->id)
            ->where('obat_id', $obat->id)
            ->firstOrFail();

        $this->assertSame(100, $detail->stok_akhir);
        $this->assertEquals(100000.0, (float) $detail->nilai_stok);

        $sdRows = DetailNeracaSumberDana::query()
            ->where('detail_neraca_id', $detail->id)
            ->get();

        $this->assertCount(1, $sdRows);

        $row = $sdRows->first();
        $this->assertNull($row->sumber_dana_id);
        $this->assertSame(100, $row->masuk_jumlah);
        $this->assertEquals(100000.0, (float) $row->masuk_nilai);
        $this->assertSame(100, $row->stok_akhir_jumlah);
        $this->assertEquals(100000.0, (float) $row->stok_akhir_nilai);
    }

    public function test_generate_mengelompokkan_mutasi_per_sumber_dana(): void
    {
        $tahun = (int) date('Y');
        $sumberDana = SumberDana::factory()->create([
            'kode' => 'DAK-'.$tahun,
            'tahun' => $tahun,
        ]);
        $obat = Obat::factory()->create(['harga_satuan' => 2000]);
        $neraca = NeracaTahunan::factory()->create(['tahun' => $tahun, 'fasilitas_id' => null]);

        $penerimaan = PenerimaanStok::factory()->create([
            'tanggal_penerimaan' => "{$tahun}-03-10",
            'fasilitas_id' => null,
            'sumber_dana_id' => $sumberDana->id,
            'total_biaya' => 60000,
        ]);

        RiwayatStok::factory()->create([
            'obat_id' => $obat->id,
            'fasilitas_id' => null,
            'tipe' => 'masuk',
            'jumlah' => 30,
            'stok_sebelum' => 0,
            'stok_sesudah' => 30,
            'tanggal' => "{$tahun}-03-11",
            'referensi_type' => PenerimaanStok::class,
            'referensi_id' => $penerimaan->id,
        ]);

        BatchStok::factory()->create([
            'obat_id' => $obat->id,
            'fasilitas_id' => null,
            'batch_number' => 'BATCH-NERACA',
            'jumlah' => 50,
            'status' => 'tersedia',
            'sumber_dana_id' => $sumberDana->id,
        ]);

        $this->service->generate($neraca);

        $detail = DetailNeracaTahunan::query()
            ->where('neraca_id', $neraca->id)
            ->where('obat_id', $obat->id)
            ->firstOrFail();

        $row = DetailNeracaSumberDana::query()
            ->where('detail_neraca_id', $detail->id)
            ->where('sumber_dana_id', $sumberDana->id)
            ->firstOrFail();

        $this->assertSame(30, $row->masuk_jumlah);
        $this->assertEquals(60000.0, (float) $row->masuk_nilai);
        $this->assertSame(50, $row->stok_akhir_jumlah);
        $this->assertSame(20, $row->stok_awal_jumlah);
        $this->assertSame(0, $row->keluar_jumlah);
    }

    public function test_sync_sumber_dana_breakdown_idempoten_dan_fallback_manual(): void
    {
        $tahun = (int) date('Y');
        $obat = Obat::factory()->create(['harga_satuan' => 1500]);
        $neraca = NeracaTahunan::factory()->create(['tahun' => $tahun, 'fasilitas_id' => null]);

        RiwayatStok::factory()->create([
            'obat_id' => $obat->id,
            'fasilitas_id' => null,
            'tanggal' => "{$tahun}-06-15",
        ]);

        $this->service->generate($neraca);

        $existingRows = DetailNeracaSumberDana::count();
        $this->assertGreaterThan(0, $existingRows);

        $manual = DetailNeracaTahunan::create([
            'neraca_id' => $neraca->id,
            'obat_id' => $obat->id,
            'stok_awal' => 5,
            'total_masuk' => 10,
            'total_keluar' => 3,
            'stok_akhir' => 12,
            'stok_optimum' => 0,
            'permintaan' => 0,
            'harga_satuan' => 1500,
            'nilai_stok' => 18000,
        ]);

        $this->service->syncSumberDanaBreakdown($manual);
        $this->service->syncSumberDanaBreakdown($manual);

        $rows = DetailNeracaSumberDana::query()
            ->where('detail_neraca_id', $manual->id)
            ->get();

        $this->assertCount(1, $rows);

        $row = $rows->first();
        $this->assertNull($row->sumber_dana_id);
        $this->assertSame(5, $row->stok_awal_jumlah);
        $this->assertSame(10, $row->masuk_jumlah);
        $this->assertSame(3, $row->keluar_jumlah);
        $this->assertSame(12, $row->stok_akhir_jumlah);
        $this->assertEquals(18000.0, (float) $row->stok_akhir_nilai);

        $this->assertSame(
            $existingRows + 1,
            DetailNeracaSumberDana::count(),
            'Sync berulang tidak boleh menduplikasi baris.'
        );
    }

    public function test_build_details_mengembalikan_bentuk_baris_form(): void
    {
        $tahun = (int) date('Y');
        $obat = Obat::factory()->create(['nama_obat' => 'Paracetamol 500mg', 'harga_satuan' => 750]);

        RiwayatStok::factory()->create([
            'obat_id' => $obat->id,
            'fasilitas_id' => null,
            'jumlah' => 40,
            'stok_sesudah' => 40,
            'tanggal' => "{$tahun}-07-01",
        ]);

        $rows = $this->service->buildDetails(null, $tahun);

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame(0, $row['_key']);
        $this->assertNull($row['id']);
        $this->assertSame($obat->id, $row['obat_id']);
        $this->assertSame('Paracetamol 500mg', $row['obat_name']);
        $this->assertSame(40, $row['stok_akhir']);
        $this->assertSame(40 * 750.0, $row['nilai_stok']);
        $this->assertArrayHasKey('keterangan', $row);
        $this->assertNull($row['keterangan']);
    }
}
