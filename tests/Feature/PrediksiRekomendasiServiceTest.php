<?php

namespace Tests\Feature;

use App\Models\DetailPemakaianObat;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\User;
use App\Services\PrediksiRekomendasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrediksiRekomendasiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faskes = FasilitasKesehatan::factory()->create();
        $this->obatA = Obat::factory()->create(['kategori' => 'Antibiotik', 'harga_satuan' => 5000, 'satuan' => 'Tablet']);
        $this->obatB = Obat::factory()->create(['kategori' => 'Vitamin', 'harga_satuan' => 1000, 'satuan' => 'Kapsul']);
    }

    private function seedPredictions(int $obatId, int $jumlah, array $months): void
    {
        foreach ($months as $m) {
            PrediksiKebutuhan::create([
                'fasilitas_id' => $this->faskes->id,
                'obat_id' => $obatId,
                'periode_bulan' => $m,
                'periode_tahun' => 2026,
                'jumlah_prediksi' => $jumlah,
                'metode' => 'ann_php',
            ]);
        }
    }

    public function test_rows_returns_grouped_rekomendasi_with_status(): void
    {
        $this->seedPredictions($this->obatA->id, 100, [10, 11, 12]);
        $this->seedPredictions($this->obatB->id, 50, [10, 11, 12]);

        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatA->id, 'jumlah' => 100]);
        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatB->id, 'jumlah' => 1000]);

        $service = new PrediksiRekomendasiService(fasilitasId: $this->faskes->id, bulan: 10, tahun: 2026, horizon: 3);

        $rows = collect($service->rows());

        $this->assertCount(2, $rows);

        $obatA = $rows->firstWhere('obat_id', $this->obatA->id);
        $obatB = $rows->firstWhere('obat_id', $this->obatB->id);

        $this->assertSame(300, $obatA['prediksi_horizon']);
        $this->assertSame(260, $obatA['rekom']);
        $this->assertSame(100, $obatA['stok']);

        $this->assertSame(150, $obatB['prediksi_horizon']);
        $this->assertSame(0, $obatB['rekom']);
        $this->assertSame('Aman', $obatB['status']);
    }

    public function test_kpi_hitung_defisit_dan_anggaran(): void
    {
        $this->seedPredictions($this->obatA->id, 100, [10, 11, 12]);
        $this->seedPredictions($this->obatB->id, 50, [10, 11, 12]);

        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatA->id, 'jumlah' => 100]);
        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatB->id, 'jumlah' => 1000]);

        $service = new PrediksiRekomendasiService(fasilitasId: $this->faskes->id, bulan: 10, tahun: 2026, horizon: 3);

        $kpi = $service->kpi();

        $this->assertSame(2, $kpi['obat_diprediksi']);
        $this->assertSame(1, $kpi['obat_defisit']);
        $this->assertEquals(260 * 5000, $kpi['estimasi_anggaran']);
    }

    public function test_horizon_satu_bulan_membatasi_periode(): void
    {
        $this->seedPredictions($this->obatA->id, 100, [10, 11, 12]);
        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatA->id, 'jumlah' => 50]);

        $service = new PrediksiRekomendasiService(fasilitasId: $this->faskes->id, bulan: 10, tahun: 2026, horizon: 1);

        $row = collect($service->rows())->firstWhere('obat_id', $this->obatA->id);

        $this->assertSame(100, $row['prediksi_horizon']);
        $this->assertSame(70, $row['rekom']);
    }

    public function test_detail_mengembalikan_rincian_lengkap(): void
    {
        foreach ([10 => 100, 11 => 120, 12 => 90] as $m => $jumlah) {
            PrediksiKebutuhan::create([
                'fasilitas_id' => $this->faskes->id,
                'obat_id' => $this->obatA->id,
                'periode_bulan' => $m,
                'periode_tahun' => 2026,
                'jumlah_prediksi' => $jumlah,
                'confidence_lower' => $jumlah - 10,
                'confidence_upper' => $jumlah + 10,
                'metode' => 'ann_php',
            ]);
        }
        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatA->id, 'jumlah' => 50]);
        StokGudang::create(['obat_id' => $this->obatA->id, 'jumlah' => 500]);
        ModelPrediksi::create([
            'fasilitas_id' => $this->faskes->id,
            'obat_id' => $this->obatA->id,
            'model_data' => '{}',
            'akurasi_r2' => 0.85,
            'tanggal_training' => now()->format('Y-m-d'),
            'data_training_count' => 6,
            'status' => 'aktif',
        ]);

        $user = User::factory()->create();
        $pemakaian = PemakaianObat::factory()->create([
            'fasilitas_id' => $this->faskes->id,
            'tanggal_pemakaian' => '2026-09-15',
            'user_id' => $user->id,
        ]);
        DetailPemakaianObat::factory()->create([
            'pemakaian_id' => $pemakaian->id,
            'obat_id' => $this->obatA->id,
            'jumlah' => 40,
        ]);

        $service = new PrediksiRekomendasiService(fasilitasId: $this->faskes->id, bulan: 10, tahun: 2026, horizon: 3);

        $detail = $service->detail($this->obatA->id);

        $this->assertNotNull($detail);
        $this->assertSame($this->obatA->id, $detail['ringkasan']['obat_id']);
        $this->assertSame(310, $detail['ringkasan']['prediksi_horizon']);
        $this->assertSame(62, $detail['safety']);
        $this->assertCount(3, $detail['bulanan']);
        $this->assertSame(100, $detail['bulanan'][0]['jumlah']);
        $this->assertSame(90, $detail['bulanan'][0]['lower']);
        $this->assertSame(110, $detail['bulanan'][0]['upper']);
        $this->assertCount(12, $detail['tren']);
        $this->assertSame(40, collect($detail['tren'])->firstWhere('label', 'Sep')['jumlah']);
        $this->assertSame('aktif', $detail['model']['status']);
        $this->assertEquals(0.85, $detail['model']['akurasi_r2']);
        $this->assertSame(500, $detail['stok_gudang']);
        $this->assertNotEmpty($detail['factors']);
    }

    public function test_detail_mengembalikan_null_untuk_obat_tanpa_data(): void
    {
        $service = new PrediksiRekomendasiService(fasilitasId: $this->faskes->id, bulan: 10, tahun: 2026, horizon: 3);

        $this->assertNull($service->detail(999999));
    }
}
