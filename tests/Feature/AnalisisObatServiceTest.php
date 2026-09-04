<?php

namespace Tests\Feature;

use App\Models\DetailPemakaianObat;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Models\User;
use App\Services\AnalisisObatService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisObatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faskes = FasilitasKesehatan::factory()->create();
        $this->obatA = Obat::factory()->create(['kategori' => 'Antibiotik', 'ven_kategori' => 'V', 'harga_satuan' => 5000]);
        $this->obatB = Obat::factory()->create(['kategori' => 'Vitamin', 'ven_kategori' => 'E', 'harga_satuan' => 1000]);
        $this->user = User::factory()->create();
    }

    private function seedPemakaian(int $obatId, string $tanggal, int $jumlah): void
    {
        $pemakaian = PemakaianObat::factory()->create([
            'fasilitas_id' => $this->faskes->id,
            'tanggal_pemakaian' => $tanggal,
            'user_id' => $this->user->id,
        ]);
        DetailPemakaianObat::factory()->create([
            'pemakaian_id' => $pemakaian->id,
            'obat_id' => $obatId,
            'jumlah' => $jumlah,
        ]);
    }

    private function seedPrediksi(int $obatId, array $bulanJumlah): void
    {
        foreach ($bulanJumlah as $m => $jumlah) {
            PrediksiKebutuhan::create([
                'fasilitas_id' => $this->faskes->id,
                'obat_id' => $obatId,
                'periode_bulan' => $m,
                'periode_tahun' => 2026,
                'jumlah_prediksi' => $jumlah,
                'metode' => 'moving_average',
            ]);
        }
    }

    public function test_kpi_menghitung_konsumsi_dan_yoy(): void
    {
        $this->seedPemakaian($this->obatA->id, '2025-03-10', 100);
        $this->seedPemakaian($this->obatA->id, '2024-03-10', 50);

        $kpi = (new AnalisisObatService(fasilitasId: $this->faskes->id, tahun: 2025))->kpi();

        $this->assertSame(100, $kpi['konsumsi']);
        $this->assertEquals(100.0, $kpi['konsumsi_yoy']);
    }

    public function test_abven_mengelompokkan_dominan_ke_a(): void
    {
        $this->seedPemakaian($this->obatA->id, '2025-03-10', 100);
        $this->seedPemakaian($this->obatB->id, '2025-04-10', 10);

        $abven = (new AnalisisObatService(fasilitasId: $this->faskes->id, tahun: 2025))->abven();

        $this->assertSame(1, $abven['matrix']['AV']);
        $this->assertSame($this->obatA->nama_obat, $abven['topA'][0]['nama_obat']);
        $this->assertEquals(500000 / 510000 * 100, $abven['topA'][0]['share']);
        $this->assertEquals(510000, $abven['total_nilai']);
        $this->assertSame('Antibiotik', $abven['topKategori']['nama']);
    }

    public function test_risiko_memetakan_probabilitas_tinggi(): void
    {
        $this->seedPrediksi($this->obatA->id, [10 => 100, 11 => 100, 12 => 100]);
        StokFaskes::create(['fasilitas_id' => $this->faskes->id, 'obat_id' => $this->obatA->id, 'jumlah' => 10]);

        $risiko = (new AnalisisObatService(fasilitasId: $this->faskes->id, tahun: 2025))->risiko(10);

        $this->assertCount(1, $risiko);
        $this->assertSame(92, $risiko[0]['prob']);
        $this->assertSame('Tinggi', $risiko[0]['prob_label']);
        $this->assertSame('Tinggi', $risiko[0]['dampak']);
        $this->assertSame(now()->addDays(3)->translatedFormat('d M Y'), $risiko[0]['habis']);
    }

    public function test_tren_mengembalikan_series_dan_musim(): void
    {
        $this->seedPrediksi($this->obatA->id, [10 => 100, 11 => 100, 12 => 100]);

        $service = new AnalisisObatService(fasilitasId: $this->faskes->id, tahun: 2025);
        $periods = $service->forecastPeriods();
        $this->assertNotEmpty($periods);

        $anchor = $periods[0];
        $bulanLalu = Carbon::create($anchor['y'], $anchor['m'], 1)->subMonth()->format('Y-m-15');
        $this->seedPemakaian($this->obatA->id, $bulanLalu, 25);

        $tren = $service->tren(3);

        $this->assertCount(12 + count($periods), $tren['labels']);
        $this->assertNotEmpty($tren['series']);
        $this->assertSame(25, $tren['series'][0]['realisasi'][11]);
        $this->assertArrayHasKey('puncak', $tren['musim']);
        $this->assertArrayHasKey('rata_bulanan', $tren['musim']);
        $this->assertArrayHasKey('tren_pct', $tren['musim']);
    }
}
