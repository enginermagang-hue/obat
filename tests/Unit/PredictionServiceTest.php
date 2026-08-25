<?php

namespace Tests\Unit;

use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Services\PredictionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PredictionServiceTest extends TestCase
{
    private const MIN_DATA_MONTHS = 6;

    private const WINDOW_MONTHS = 12;

    private PredictionService $predictionService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => $this->devDatabaseName()]);

        DB::purge('mysql');
        DB::setDefaultConnection('mysql');

        $this->predictionService = app(PredictionService::class);
    }

    public function test_get_monthly_usage_untuk_semua_puskesmas(): void
    {
        $combinations = $this->getAllFaskesObatCombinations();

        $this->assertNotEmpty($combinations, 'Tidak ada kombinasi faskes+obat di database.');

        foreach ($combinations as $combo) {
            $actual = $this->predictionService->getMonthlyUsage($combo->fasilitas_id, $combo->obat_id);
            $expected = $this->getExpectedMonthlyUsage($combo->fasilitas_id, $combo->obat_id);

            $this->assertSame(
                $expected,
                $actual,
                "getMonthlyUsage mismatch untuk faskes={$combo->fasilitas_id}, obat={$combo->obat_id}",
            );
        }
    }

    public function test_train_semua_puskesmas_dengan_data_cukup(): void
    {
        $combinations = collect($this->getAllFaskesObatCombinations())
            ->filter(fn ($combo): bool => $combo->months >= self::MIN_DATA_MONTHS);

        $this->assertNotEmpty($combinations, 'Tidak ada kombinasi faskes+obat dengan data mencukupi.');

        foreach ($combinations as $combo) {
            $faskes = FasilitasKesehatan::find($combo->fasilitas_id);
            $obat = Obat::find($combo->obat_id);

            if ($faskes === null || $obat === null) {
                continue;
            }

            $model = $this->predictionService->train($faskes, $obat);

            $this->assertSame(
                'aktif',
                $model->status,
                "Train gagal aktif untuk faskes={$combo->fasilitas_id}, obat={$combo->obat_id}",
            );
            $this->assertNotEmpty($model->model_data);
            $this->assertSame($combo->months, $model->data_training_count);
            $this->assertCount(9, $model->fitur_digunakan);
        }
    }

    public function test_train_puskesmas_data_tidak_cukup(): void
    {
        $combinations = collect($this->getAllFaskesObatCombinations())
            ->filter(fn ($combo): bool => $combo->months < self::MIN_DATA_MONTHS);

        $this->assertNotEmpty($combinations, 'Tidak ada kombinasi faskes+obat dengan data tidak mencukupi.');

        foreach ($combinations as $combo) {
            $faskes = FasilitasKesehatan::find($combo->fasilitas_id);
            $obat = Obat::find($combo->obat_id);

            if ($faskes === null || $obat === null) {
                continue;
            }

            $model = $this->predictionService->train($faskes, $obat);

            $this->assertSame(
                'data_belum_cukup',
                $model->status,
                "Train status salah untuk faskes={$combo->fasilitas_id}, obat={$combo->obat_id}",
            );
            $this->assertSame('', $model->model_data);
            $this->assertSame($combo->months, $model->data_training_count);
        }
    }

    public function test_generate_predictions_untuk_model_aktif(): void
    {
        $combinations = collect($this->getAllFaskesObatCombinations())
            ->filter(fn ($combo): bool => $combo->months >= self::MIN_DATA_MONTHS);

        foreach ($combinations as $combo) {
            $faskes = FasilitasKesehatan::find($combo->fasilitas_id);
            $obat = Obat::find($combo->obat_id);

            if ($faskes === null || $obat === null) {
                continue;
            }

            $model = $this->predictionService->train($faskes, $obat);

            $predictions = $this->predictionService->generatePredictions($model);

            $this->assertCount(
                3,
                $predictions,
                "Jumlah prediksi salah untuk faskes={$combo->fasilitas_id}, obat={$combo->obat_id}",
            );
            $this->assertSame(
                'ai_gradient_boost',
                $predictions->first()->metode,
                "Metode prediksi salah untuk faskes={$combo->fasilitas_id}, obat={$combo->obat_id}",
            );
            $this->assertGreaterThanOrEqual(0, $predictions->first()->jumlah_prediksi);
        }
    }

    public function test_generate_predictions_untuk_model_data_kurang(): void
    {
        $combination = collect($this->getAllFaskesObatCombinations())
            ->filter(fn ($combo): bool => $combo->months < self::MIN_DATA_MONTHS)
            ->first();

        $this->assertNotNull($combination, 'Tidak ada kombinasi faskes+obat dengan data tidak mencukupi.');

        $model = ModelPrediksi::updateOrCreate(
            [
                'fasilitas_id' => $combination->fasilitas_id,
                'obat_id' => $combination->obat_id,
            ],
            [
                'model_data' => '',
                'tanggal_training' => now(),
                'data_training_count' => $combination->months,
                'status' => 'data_belum_cukup',
            ],
        );

        $predictions = $this->predictionService->generatePredictions($model);

        $this->assertCount(3, $predictions);
        $this->assertSame('moving_average', $predictions->first()->metode);
        $this->assertGreaterThanOrEqual(0, $predictions->first()->jumlah_prediksi);
    }

    public function test_generate_predictions_mengabaikan_model_tidak_aktif(): void
    {
        $faskes = FasilitasKesehatan::where('tipe', 'puskesmas')->first();
        $obat = Obat::first();

        $this->assertNotNull($faskes, 'Tidak ada puskesmas di database.');
        $this->assertNotNull($obat, 'Tidak ada obat di database.');

        $model = ModelPrediksi::updateOrCreate(
            [
                'fasilitas_id' => $faskes->id,
                'obat_id' => $obat->id,
            ],
            [
                'model_data' => '',
                'tanggal_training' => now(),
                'data_training_count' => 0,
                'status' => 'gagal',
            ],
        );

        $predictions = $this->predictionService->generatePredictions($model);

        $this->assertCount(0, $predictions);
    }

    /**
     * Ambil nama database development yang sebenarnya (di luar override phpunit).
     */
    private function devDatabaseName(): string
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return 'obat';
        }

        $match = collect(file($envPath, FILE_IGNORE_NEW_LINES))
            ->map(fn ($line): string => trim($line))
            ->first(fn ($line): bool => str_starts_with($line, 'DB_DATABASE='));

        return $match !== null
            ? rtrim(substr($match, strlen('DB_DATABASE=')), "\" '")
            : 'obat';
    }

    /**
     * Ambil semua kombinasi faskes+obat dengan jumlah bulan data dalam 12 bulan terakhir.
     *
     * @return array<int, object{fasilitas_id: int, obat_id: int, months: int}>
     */
    private function getAllFaskesObatCombinations(): array
    {
        $startDate = now()->subMonths(self::WINDOW_MONTHS)->startOfMonth();

        $bulanExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', p.tanggal_pemakaian)"
            : "DATE_FORMAT(p.tanggal_pemakaian, '%Y-%m')";

        return DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->selectRaw("p.fasilitas_id as fasilitas_id, d.obat_id as obat_id, COUNT(DISTINCT {$bulanExpression}) as months")
            ->where('p.tanggal_pemakaian', '>=', $startDate->toDateString())
            ->groupBy('p.fasilitas_id', 'd.obat_id')
            ->orderBy('p.fasilitas_id')
            ->orderBy('d.obat_id')
            ->get()
            ->map(fn ($row): object => (object) [
                'fasilitas_id' => (int) $row->fasilitas_id,
                'obat_id' => (int) $row->obat_id,
                'months' => (int) $row->months,
            ])
            ->all();
    }

    /**
     * Hitung usage bulanan yang diharapkan langsung dari database.
     *
     * @return array<string, int>
     */
    private function getExpectedMonthlyUsage(int $fasilitasId, int $obatId): array
    {
        $startDate = now()->subMonths(self::WINDOW_MONTHS)->startOfMonth();

        $bulanExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', p.tanggal_pemakaian)"
            : "DATE_FORMAT(p.tanggal_pemakaian, '%Y-%m')";

        return DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->selectRaw("{$bulanExpression} as bulan, SUM(d.jumlah) as total")
            ->where('p.fasilitas_id', $fasilitasId)
            ->where('d.obat_id', $obatId)
            ->where('p.tanggal_pemakaian', '>=', $startDate->toDateString())
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn ($v): int => (int) $v)
            ->toArray();
    }
}
