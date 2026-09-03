<?php

namespace Tests\Unit;

use App\Models\DetailPemakaianObat;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Services\PhpAnnPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhpAnnPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MIN_DATA_MONTHS = 6;

    private const WINDOW_MONTHS = 12;

    private PhpAnnPredictionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PhpAnnPredictionService::class);
        if (empty($this->getAllCombinations())) {
            $this->seedMinimalPemakaianData();
        }
    }

    private function seedMinimalPemakaianData(): void
    {
        $f1 = FasilitasKesehatan::factory()->create(['tipe' => 'puskesmas']);
        $o1 = Obat::factory()->create();
        $o2 = Obat::factory()->create();
        for ($i = 9; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth()->addDays(5);
            $p = PemakaianObat::factory()->create(['fasilitas_id' => $f1->id, 'tanggal_pemakaian' => $date->toDateString()]);
            DetailPemakaianObat::factory()->create(['pemakaian_id' => $p->id, 'obat_id' => $o1->id, 'jumlah' => random_int(10, 50)]);
        }
        $f2 = FasilitasKesehatan::factory()->create(['tipe' => 'puskesmas']);
        $o3 = Obat::factory()->create();
        for ($i = 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth()->addDays(5);
            $p = PemakaianObat::factory()->create(['fasilitas_id' => $f2->id, 'tanggal_pemakaian' => $date->toDateString()]);
            DetailPemakaianObat::factory()->create(['pemakaian_id' => $p->id, 'obat_id' => $o2->id, 'jumlah' => random_int(5, 20)]);
            $p2 = PemakaianObat::factory()->create(['fasilitas_id' => $f2->id, 'tanggal_pemakaian' => $date->toDateString()]);
            DetailPemakaianObat::factory()->create(['pemakaian_id' => $p2->id, 'obat_id' => $o3->id, 'jumlah' => random_int(5, 20)]);
        }
    }

    public function test_get_monthly_usage(): void
    {
        $row = DB::table('detail_pemakaian_obat as d')->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')->selectRaw('p.fasilitas_id, d.obat_id')->first();
        $this->assertNotNull($row);
        $actual = $this->service->getMonthlyUsage($row->fasilitas_id, $row->obat_id);
        $this->assertCount(12, $actual);
    }

    public function test_train_per_kombinasi_dengan_data_cukup(): void
    {
        $combos = collect($this->getAllCombinations())->filter(fn ($c) => $c->months >= self::MIN_DATA_MONTHS);
        $this->assertNotEmpty($combos);
        foreach ($combos as $c) {
            $faskes = FasilitasKesehatan::find($c->fasilitas_id);
            $obat = Obat::find($c->obat_id);
            $model = $this->service->train($faskes, $obat);
            $this->assertSame('aktif', $model->status);
            $this->assertNotEmpty($model->model_data);
            $this->assertCount(9, $model->fitur_digunakan);
        }
    }

    public function test_train_data_tidak_cukup(): void
    {
        $combos = collect($this->getAllCombinations())->filter(fn ($c) => $c->months < self::MIN_DATA_MONTHS);
        if ($combos->isEmpty()) {
            $this->markTestSkipped('No insufficient data combo');
        }
        $c = $combos->first();
        $faskes = FasilitasKesehatan::find($c->fasilitas_id);
        $obat = Obat::find($c->obat_id);
        $model = $this->service->train($faskes, $obat);
        $this->assertSame('data_belum_cukup', $model->status);
    }

    public function test_generate_predictions_aktif(): void
    {
        $combo = collect($this->getAllCombinations())->filter(fn ($c) => $c->months >= self::MIN_DATA_MONTHS)->first();
        $faskes = FasilitasKesehatan::find($combo->fasilitas_id);
        $obat = Obat::find($combo->obat_id);
        $model = $this->service->train($faskes, $obat);
        $preds = $this->service->generatePredictions($model);
        $this->assertCount(3, $preds);
        $this->assertSame('ann_php', $preds->first()->metode);
    }

    public function test_generate_predictions_data_kurang(): void
    {
        $combo = collect($this->getAllCombinations())->filter(fn ($c) => $c->months < self::MIN_DATA_MONTHS)->first();
        if (! $combo) {
            $this->markTestSkipped('No insufficient combo');
        }
        $model = ModelPrediksi::updateOrCreate(['fasilitas_id' => $combo->fasilitas_id, 'obat_id' => $combo->obat_id], ['model_data' => '', 'tanggal_training' => now(), 'data_training_count' => $combo->months, 'status' => 'data_belum_cukup']);
        $preds = $this->service->generatePredictions($model);
        $this->assertCount(3, $preds);
        $this->assertSame('moving_average', $preds->first()->metode);
    }

    public function test_generate_mengabaikan_gagal(): void
    {
        $faskes = FasilitasKesehatan::first();
        $obat = Obat::first();
        $model = ModelPrediksi::updateOrCreate(['fasilitas_id' => $faskes->id, 'obat_id' => $obat->id], ['model_data' => '', 'tanggal_training' => now(), 'data_training_count' => 0, 'status' => 'gagal']);
        $preds = $this->service->generatePredictions($model);
        $this->assertCount(0, $preds);
    }

    private function getAllCombinations(): array
    {
        $bulan = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', p.tanggal_pemakaian)" : "DATE_FORMAT(p.tanggal_pemakaian, '%Y-%m')";

        return DB::table('detail_pemakaian_obat as d')->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')->selectRaw("p.fasilitas_id as fasilitas_id, d.obat_id as obat_id, COUNT(DISTINCT {$bulan}) as months")->groupBy('p.fasilitas_id', 'd.obat_id')->get()->map(fn ($r) => (object) ['fasilitas_id' => (int) $r->fasilitas_id, 'obat_id' => (int) $r->obat_id, 'months' => (int) $r->months])->all();
    }
}
