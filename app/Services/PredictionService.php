<?php

namespace App\Services;

use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Rubix\ML\CrossValidation\Metrics\RSquared;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Encoding;
use Rubix\ML\Regressors\GradientBoost;
use Rubix\ML\Regressors\RegressionTree;
use Rubix\ML\Serializers\RBX;

class PredictionService
{
    private const MIN_DATA_MONTHS = 6;

    private const PREDICTION_MONTHS_AHEAD = 3;

    private const WINDOW_MONTHS = 12;

    private const TRAIN_TEST_SPLIT = 0.8;

    public function __construct(
        private readonly MovingAverageService $movingAverage,
    ) {}

    /**
     * Train a prediction model for a specific facility + drug combination.
     */
    public function train(FasilitasKesehatan $faskes, Obat $obat): ModelPrediksi
    {
        $monthlyData = $this->getMonthlyUsage($faskes->id, $obat->id);

        // Count months with actual usage (>0) to keep the 6-month threshold meaningful after zero-fill.
        $distinctMonths = count(array_filter($monthlyData, fn (int $v): bool => $v > 0));
        $dataCount = $distinctMonths;

        if ($dataCount < self::MIN_DATA_MONTHS) {
            return $this->createInsufficientDataModel($faskes, $obat, $dataCount);
        }

        try {
            $featureVectors = [];
            $labels = [];

            $keys = array_keys($monthlyData);

            for ($i = 3; $i < count($keys); $i++) {
                $currentKey = $keys[$i];
                $currentValue = $monthlyData[$currentKey];

                $features = $this->buildFeatureVector(
                    $monthlyData,
                    $currentKey,
                    $faskes,
                    $obat,
                );

                $featureVectors[] = $features;
                $labels[] = (int) $currentValue;
            }

            if (count($featureVectors) < 2) {
                return $this->createInsufficientDataModel($faskes, $obat, $dataCount);
            }

            $splitIndex = (int) floor(count($featureVectors) * self::TRAIN_TEST_SPLIT);
            $hasTestSet = (count($featureVectors) - $splitIndex) >= 2;

            $trainFeatures = array_slice($featureVectors, 0, $splitIndex);
            $trainLabels = array_slice($labels, 0, $splitIndex);
            $testFeatures = array_slice($featureVectors, $splitIndex);
            $testLabels = array_slice($labels, $splitIndex);

            $trainDataset = new Labeled($trainFeatures, $trainLabels);

            $estimator = new GradientBoost(
                new RegressionTree(3),
                0.1,
                0.8,
                1000,
            );
            $estimator->train($trainDataset);

            if ($hasTestSet) {
                $testDataset = new Labeled($testFeatures, $testLabels);
                $testPredictions = $estimator->predict($testDataset);
                $rSquared = (new RSquared)->score($testPredictions, $testLabels);
            } else {
                $rSquared = null;
            }

            $featureNames = $this->getFeatureNames();

            $serialized = $this->serializeModel($estimator);

            $model = ModelPrediksi::updateOrCreate(
                [
                    'fasilitas_id' => $faskes->id,
                    'obat_id' => $obat->id,
                ],
                [
                    'model_data' => $serialized,
                    'akurasi_r2' => $rSquared !== null ? max(0, min(1, $rSquared)) : null,
                    'tanggal_training' => now(),
                    'data_training_count' => $dataCount,
                    'fitur_digunakan' => $featureNames,
                    'status' => 'aktif',
                    'error_message' => null,
                ],
            );

            ModelPrediksi::where('fasilitas_id', $faskes->id)
                ->where('obat_id', $obat->id)
                ->where('id', '!=', $model->id)
                ->where('status', 'aktif')
                ->update(['status' => 'kadaluarsa']);

            return $model;
        } catch (\Throwable $e) {
            return $this->createFailedModel($faskes, $obat, $dataCount, $e->getMessage());
        }
    }

    /**
     * Generate predictions for the given trained model.
     *
     * @return Collection<int, PrediksiKebutuhan>
     */
    public function generatePredictions(ModelPrediksi $model): Collection
    {
        $now = now();

        $predictions = new Collection;

        if ($model->status === 'data_belum_cukup') {
            $monthlyData = $this->getMonthlyUsage($model->fasilitas_id, $model->obat_id);
            $monthlyValues = array_values($monthlyData);

            for ($i = 1; $i <= self::PREDICTION_MONTHS_AHEAD; $i++) {
                $maResult = $this->movingAverage->predict($monthlyValues);
                $targetMonth = $now->copy()->addMonthsNoOverflow($i);

                $prediction = $this->savePrediction(
                    model: $model,
                    fasilitasId: $model->fasilitas_id,
                    obatId: $model->obat_id,
                    bulan: (int) $targetMonth->format('n'),
                    tahun: (int) $targetMonth->format('Y'),
                    jumlahPrediksi: $maResult['jumlah'],
                    confidenceLower: $maResult['confidence_lower'],
                    confidenceUpper: $maResult['confidence_upper'],
                    metode: 'moving_average',
                );
                $predictions->push($prediction);

                $monthlyValues[] = $maResult['jumlah'];
            }

            return $predictions;
        }

        if ($model->status !== 'aktif' || blank($model->model_data)) {
            return $predictions;
        }

        try {
            $estimator = $this->deserializeModel($model->model_data);
        } catch (\Throwable) {
            return $predictions;
        }

        $historicalData = $this->getMonthlyUsage($model->fasilitas_id, $model->obat_id);
        $historicalValues = array_values($historicalData);
        $predictedSoFar = [];

        for ($i = 1; $i <= self::PREDICTION_MONTHS_AHEAD; $i++) {
            $targetMonth = $now->copy()->addMonthsNoOverflow($i);
            $monthKey = $targetMonth->format('Y-m');

            $features = $this->buildFeatureVector(
                $historicalData,
                $monthKey,
                $model->fasilitas,
                $model->obat,
                true,
                $predictedSoFar,
            );

            try {
                $dataset = new Unlabeled([$features]);
                $predictedAmount = (int) round(max(0, $estimator->predict($dataset)[0]));

                $predictedSoFar[] = $predictedAmount;

                $stdDev = $this->calculateStdDev($historicalValues);
                $margin = (int) round(1.96 * $stdDev);

                $prediction = $this->savePrediction(
                    model: $model,
                    fasilitasId: $model->fasilitas_id,
                    obatId: $model->obat_id,
                    bulan: (int) $targetMonth->format('n'),
                    tahun: (int) $targetMonth->format('Y'),
                    jumlahPrediksi: $predictedAmount,
                    confidenceLower: max(0, $predictedAmount - $margin),
                    confidenceUpper: $predictedAmount + $margin,
                    metode: 'ai_gradient_boost',
                );
                $predictions->push($prediction);
            } catch (\Throwable) {
                continue;
            }
        }

        return $predictions;
    }

    /**
     * Get monthly aggregated usage data for a facility + drug.
     * Zero-filled for the last WINDOW_MONTHS months so lag features are calendar-correct.
     *
     * @return array<string, int> key = 'Y-m', value = total usage
     */
    public function getMonthlyUsage(int $fasilitasId, int $obatId): array
    {
        $startDate = now()->subMonths(self::WINDOW_MONTHS)->startOfMonth();

        $bulanExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', p.tanggal_pemakaian)"
            : "DATE_FORMAT(p.tanggal_pemakaian, '%Y-%m')";

        $records = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->selectRaw("{$bulanExpression} as bulan, SUM(d.jumlah) as total")
            ->where('p.fasilitas_id', $fasilitasId)
            ->where('d.obat_id', $obatId)
            ->where('p.tanggal_pemakaian', '>=', $startDate)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn ($v): int => (int) $v)
            ->toArray();

        // Zero-fill from startDate month through current month (inclusive) to keep lag indices stable.
        $filled = [];
        $cursor = $startDate->copy()->startOfMonth();
        $end = now()->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $filled[$key] = $records[$key] ?? 0;
            $cursor->addMonth();
        }

        return $filled;
    }

    /**
     * Build feature vector for a given month.
     *
     * @param  array<string, int>  $monthlyData
     * @param  array<int, int>  $predictedSoFar  previously predicted values for lag chaining
     * @return array<int, float>
     */
    private function buildFeatureVector(
        array $monthlyData,
        string $targetKey,
        FasilitasKesehatan $faskes,
        Obat $obat,
        bool $predictionMode = false,
        array $predictedSoFar = [],
    ): array {
        $keys = array_keys($monthlyData);
        $values = array_values($monthlyData);

        $targetIndex = array_search($targetKey, $keys, true);

        if ($targetIndex === false) {
            $targetIndex = count($keys);
        }

        $combinedValues = array_merge($values, $predictedSoFar);
        $combinedCount = count($combinedValues);

        $lag1 = $combinedCount >= 1 ? $combinedValues[$combinedCount - 1] : 0;
        $lag2 = $combinedCount >= 2 ? $combinedValues[$combinedCount - 2] : 0;
        $lag3 = $combinedCount >= 3 ? $combinedValues[$combinedCount - 3] : 0;

        // Unified window logic: use effective history up to target (plus predictions when forecasting).
        $effectiveValues = $predictionMode ? $combinedValues : array_slice($values, 0, $targetIndex + 1);
        $avg6Values = array_slice($effectiveValues, -6);
        $avg12Values = array_slice($effectiveValues, -12);

        $avg6 = ! empty($avg6Values) ? array_sum($avg6Values) / count($avg6Values) : 0;
        $avg12 = ! empty($avg12Values) ? array_sum($avg12Values) / count($avg12Values) : 0;

        $month = (int) Carbon::parse($targetKey.'-01')->format('n');

        $trendValues = array_slice($effectiveValues, -3);
        $trend = $this->calculateTrend($trendValues, 3);

        $currentStock = $this->getCurrentStock($faskes->id, $obat->id);

        $tipeFaskes = $faskes->tipe === 'puskesmas' ? 1.0 : 0.0;

        return [
            (float) $lag1,
            (float) $lag2,
            (float) $lag3,
            (float) $avg6,
            (float) $avg12,
            (float) $month,
            (float) $trend,
            (float) $currentStock,
            $tipeFaskes,
        ];
    }

    /**
     * Get list of feature names for reference.
     *
     * @return array<string>
     */
    private function getFeatureNames(): array
    {
        return [
            'lag_1_bulan',
            'lag_2_bulan',
            'lag_3_bulan',
            'rata_rata_6_bulan',
            'rata_rata_12_bulan',
            'bulan',
            'trend_3_bulan',
            'stok_saat_ini',
            'tipe_faskes',
        ];
    }

    /**
     * Calculate trend (slope) using least-squares linear regression.
     */
    private function calculateTrend(array $values, int $window): float
    {
        $recent = array_slice($values, -$window);
        $count = count($recent);

        if ($count < 2) {
            return 0;
        }

        $x = range(0, $count - 1);
        $xMean = ($count - 1) / 2;
        $ySum = array_sum($recent);
        $yMean = $ySum / $count;

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $count; $i++) {
            $dx = $x[$i] - $xMean;
            $numerator += $dx * ($recent[$i] - $yMean);
            $denominator += $dx ** 2;
        }

        if ($denominator == 0) {
            return 0;
        }

        return (float) ($numerator / $denominator);
    }

    /**
     * Calculate sample standard deviation (Bessel's correction: n-1).
     */
    private function calculateStdDev(array $values): float
    {
        $count = count($values);

        if ($count <= 1) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn ($v): float => ($v - $mean) ** 2, $values)) / ($count - 1);

        return sqrt($variance);
    }

    /**
     * Get current stock for a facility + drug.
     * Aggregates StokFaskes if present, else falls back to BatchStok sum for robustness.
     */
    private function getCurrentStock(int $fasilitasId, int $obatId): int
    {
        $stok = StokFaskes::query()
            ->where('fasilitas_id', $fasilitasId)
            ->where('obat_id', $obatId)
            ->value('jumlah');

        if ($stok !== null) {
            return (int) $stok;
        }

        // Fallback to batch-level stock (tersedia) when aggregate row missing.
        $batchSum = BatchStok::query()
            ->where('obat_id', $obatId)
            ->where('status', 'tersedia')
            ->where('jumlah', '>', 0)
            ->where('fasilitas_id', $fasilitasId)
            ->sum('jumlah');

        if ($batchSum > 0) {
            return (int) $batchSum;
        }

        $faskes = FasilitasKesehatan::find($fasilitasId);

        if ($faskes?->tipe === 'gudang') {
            $gudangJumlah = StokGudang::query()->where('obat_id', $obatId)->value('jumlah');

            if ($gudangJumlah !== null) {
                return (int) $gudangJumlah;
            }

            return (int) BatchStok::query()
                ->where('obat_id', $obatId)
                ->whereNull('fasilitas_id')
                ->where('status', 'tersedia')
                ->sum('jumlah');
        }

        return 0;
    }

    /**
     * Serialize Rubix/ML estimator to base64 string for DB storage.
     */
    private function serializeModel(object $estimator): string
    {
        return base64_encode((new RBX)->serialize($estimator)->data());
    }

    /**
     * Deserialize Rubix/ML estimator from base64 DB string.
     */
    private function deserializeModel(string $data): object
    {
        return (new RBX)->deserialize(new Encoding(base64_decode($data)));
    }

    /**
     * Save a prediction record.
     */
    private function savePrediction(
        ?ModelPrediksi $model,
        int $fasilitasId,
        int $obatId,
        int $bulan,
        int $tahun,
        int $jumlahPrediksi,
        int $confidenceLower,
        int $confidenceUpper,
        string $metode,
    ): PrediksiKebutuhan {
        return PrediksiKebutuhan::updateOrCreate(
            [
                'fasilitas_id' => $fasilitasId,
                'obat_id' => $obatId,
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
            ],
            [
                'model_id' => $model?->id,
                'jumlah_prediksi' => $jumlahPrediksi,
                'confidence_lower' => $confidenceLower,
                'confidence_upper' => $confidenceUpper,
                'metode' => $metode,
                'dibuat_oleh' => null,
            ],
        );
    }

    /**
     * Create/update model with 'data_belum_cukup' status.
     */
    private function createInsufficientDataModel(
        FasilitasKesehatan $faskes,
        Obat $obat,
        int $dataCount,
    ): ModelPrediksi {
        return ModelPrediksi::updateOrCreate(
            [
                'fasilitas_id' => $faskes->id,
                'obat_id' => $obat->id,
            ],
            [
                'model_data' => '',
                'akurasi_r2' => null,
                'tanggal_training' => now(),
                'data_training_count' => $dataCount,
                'fitur_digunakan' => null,
                'status' => 'data_belum_cukup',
                'error_message' => null,
            ],
        );
    }

    /**
     * Create/update model with 'gagal' status.
     */
    private function createFailedModel(
        FasilitasKesehatan $faskes,
        Obat $obat,
        int $dataCount,
        string $errorMessage,
    ): ModelPrediksi {
        return ModelPrediksi::updateOrCreate(
            [
                'fasilitas_id' => $faskes->id,
                'obat_id' => $obat->id,
            ],
            [
                'model_data' => '',
                'akurasi_r2' => null,
                'tanggal_training' => now(),
                'data_training_count' => $dataCount,
                'fitur_digunakan' => null,
                'status' => 'gagal',
                'error_message' => $errorMessage,
            ],
        );
    }
}
