<?php

namespace App\Services;

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

        $dataCount = count($monthlyData);

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

                $prediction = $this->savePrediction(
                    model: $model,
                    fasilitasId: $model->fasilitas_id,
                    obatId: $model->obat_id,
                    bulan: (int) $targetMonth->format('n'),
                    tahun: (int) $targetMonth->format('Y'),
                    jumlahPrediksi: $predictedAmount,
                    confidenceLower: max(0, $predictedAmount - (int) round($stdDev)),
                    confidenceUpper: $predictedAmount + (int) round($stdDev),
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

        return $records;
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

        if ($predictionMode) {
            $avg6Values = array_slice($combinedValues, -6);
            $avg12Values = array_slice($combinedValues, -12);
        } else {
            $sliceEnd = $targetIndex + 1;
            $avg6Values = array_slice($values, max(0, $sliceEnd - 6), 6);
            $avg12Values = array_slice($values, max(0, $sliceEnd - 12), 12);
        }

        $avg6 = ! empty($avg6Values) ? array_sum($avg6Values) / count($avg6Values) : 0;
        $avg12 = ! empty($avg12Values) ? array_sum($avg12Values) / count($avg12Values) : 0;

        $month = $targetIndex !== false && isset($keys[$targetIndex])
            ? (int) Carbon::parse($keys[$targetIndex].'-01')->format('n')
            : (int) Carbon::parse($targetKey.'-01')->format('n');

        $trendValues = $predictionMode
            ? array_slice($combinedValues, -3)
            : array_slice($values, max(0, $targetIndex - 2), 3);
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
     */
    private function getCurrentStock(int $fasilitasId, int $obatId): int
    {
        $stok = StokFaskes::query()
            ->where('fasilitas_id', $fasilitasId)
            ->where('obat_id', $obatId)
            ->first();

        if ($stok !== null) {
            return $stok->jumlah;
        }

        $faskes = FasilitasKesehatan::find($fasilitasId);

        if ($faskes?->tipe === 'gudang') {
            return StokGudang::query()
                ->where('obat_id', $obatId)
                ->first()?->jumlah ?? 0;
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
