<?php

namespace App\Services;

use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Models\PrediksiKebutuhan;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Services\Ann\AnnModel;
use App\Services\Ann\AnnTrainer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhpAnnPredictionService
{
    private const MIN_DATA_MONTHS = 6;

    private const PREDICTION_MONTHS_AHEAD = 3;

    private const WINDOW_MONTHS = 12;

    private const TRAIN_TEST_SPLIT = 0.8;

    public function __construct(
        private readonly MovingAverageService $movingAverage,
        private readonly AnnTrainer $trainer = new AnnTrainer(hiddenLayers: [12, 8]),
    ) {}

    public function train(FasilitasKesehatan $faskes, Obat $obat): ModelPrediksi
    {
        $monthlyData = $this->getMonthlyUsage($faskes->id, $obat->id);
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
                $features = $this->buildFeatureVector($monthlyData, $keys[$i], $faskes, $obat);
                $featureVectors[] = $features;
                $labels[] = (float) $monthlyData[$keys[$i]];
            }

            if (count($featureVectors) < 2) {
                return $this->createInsufficientDataModel($faskes, $obat, $dataCount);
            }

            $splitIndex = (int) floor(count($featureVectors) * self::TRAIN_TEST_SPLIT);
            $hasTestSet = (count($featureVectors) - $splitIndex) >= 2;

            $trainX = array_slice($featureVectors, 0, $splitIndex);
            $trainY = array_slice($labels, 0, $splitIndex);
            $testX = array_slice($featureVectors, $splitIndex);
            $testY = array_slice($labels, $splitIndex);

            $model = $this->trainer->train($trainX, $trainY);

            $r2 = null;
            $mae = null;
            $mape = null;
            if ($hasTestSet) {
                $preds = array_map(fn ($x) => $model->predict($x), $testX);
                $r2 = $this->rSquared($preds, $testY);
                $mae = $this->mae($preds, $testY);
                $mape = $this->mape($preds, $testY);
            }

            $path = $this->saveModelFile($faskes->id, $obat->id, $model);

            $record = ModelPrediksi::updateOrCreate(
                ['fasilitas_id' => $faskes->id, 'obat_id' => $obat->id],
                [
                    'model_data' => json_encode($model->toArray()),
                    'model_path' => $path,
                    'akurasi_r2' => $r2 !== null ? max(0, min(1, $r2)) : null,
                    'mae' => $mae,
                    'mape' => $mape,
                    'tanggal_training' => now(),
                    'data_training_count' => $dataCount,
                    'fitur_digunakan' => $this->getFeatureNames(),
                    'status' => 'aktif',
                    'error_message' => null,
                ],
            );

            ModelPrediksi::where('fasilitas_id', $faskes->id)->where('obat_id', $obat->id)->where('id', '!=', $record->id)->where('status', 'aktif')->update(['status' => 'kadaluarsa']);

            return $record;
        } catch (\Throwable $e) {
            return $this->createFailedModel($faskes, $obat, $dataCount, $e->getMessage());
        }
    }

    public function generatePredictions(ModelPrediksi $model): Collection
    {
        $now = now();
        $predictions = new Collection;

        if ($model->status === 'data_belum_cukup') {
            $monthlyValues = array_values($this->getMonthlyUsage($model->fasilitas_id, $model->obat_id));
            for ($i = 1; $i <= self::PREDICTION_MONTHS_AHEAD; $i++) {
                $ma = $this->movingAverage->predict($monthlyValues);
                $target = $now->copy()->addMonthsNoOverflow($i);
                $predictions->push($this->savePrediction($model, $model->fasilitas_id, $model->obat_id, (int) $target->format('n'), (int) $target->format('Y'), $ma['jumlah'], $ma['confidence_lower'], $ma['confidence_upper'], 'moving_average'));
                $monthlyValues[] = $ma['jumlah'];
            }

            return $predictions;
        }

        if ($model->status !== 'aktif') {
            return $predictions;
        }

        try {
            $ann = $this->loadModel($model);
        } catch (\Throwable) {
            return $predictions;
        }

        $historicalData = $this->getMonthlyUsage($model->fasilitas_id, $model->obat_id);
        $historicalValues = array_values($historicalData);
        $predictedSoFar = [];

        for ($i = 1; $i <= self::PREDICTION_MONTHS_AHEAD; $i++) {
            $target = $now->copy()->addMonthsNoOverflow($i);
            $monthKey = $target->format('Y-m');
            $features = $this->buildFeatureVector($historicalData, $monthKey, $model->fasilitas, $model->obat, true, $predictedSoFar);
            $amount = (int) round(max(0, $ann->predict($features)));
            $predictedSoFar[] = $amount;
            $std = $this->stdDev($historicalValues);
            $margin = (int) round(1.96 * $std);
            $predictions->push($this->savePrediction($model, $model->fasilitas_id, $model->obat_id, (int) $target->format('n'), (int) $target->format('Y'), $amount, max(0, $amount - $margin), $amount + $margin, 'ann_php'));
        }

        return $predictions;
    }

    public function getMonthlyUsage(int $fasilitasId, int $obatId): array
    {
        $lastDate = DB::table('pemakaian_obat')->where('fasilitas_id', $fasilitasId)->max('tanggal_pemakaian');
        $anchor = $lastDate ? Carbon::parse($lastDate)->startOfMonth() : now()->copy()->startOfMonth();
        $startDate = $anchor->copy()->subMonths(self::WINDOW_MONTHS - 1)->startOfMonth();
        $expr = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', p.tanggal_pemakaian)" : "DATE_FORMAT(p.tanggal_pemakaian, '%Y-%m')";
        $records = DB::table('detail_pemakaian_obat as d')->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')->selectRaw("{$expr} as bulan, SUM(d.jumlah) as total")->where('p.fasilitas_id', $fasilitasId)->where('d.obat_id', $obatId)->where('p.tanggal_pemakaian', '>=', $startDate)->groupBy('bulan')->orderBy('bulan')->pluck('total', 'bulan')->map(fn ($v): int => (int) $v)->toArray();
        $filled = [];
        $cursor = $startDate->copy()->startOfMonth();
        while ($cursor->lte($anchor)) {
            $filled[$cursor->format('Y-m')] = $records[$cursor->format('Y-m')] ?? 0;
            $cursor->addMonth();
        }

        return $filled;
    }

    private function buildFeatureVector(array $monthlyData, string $targetKey, FasilitasKesehatan $faskes, Obat $obat, bool $predictionMode = false, array $predictedSoFar = []): array
    {
        $values = array_values($monthlyData);
        $keys = array_keys($monthlyData);
        $targetIndex = array_search($targetKey, $keys, true);
        if ($targetIndex === false) {
            $targetIndex = count($keys);
        }
        $combined = array_merge($values, $predictedSoFar);
        $lag1 = $combined[count($combined) - 1] ?? 0;
        $lag2 = $combined[count($combined) - 2] ?? 0;
        $lag3 = $combined[count($combined) - 3] ?? 0;
        $effective = $predictionMode ? $combined : array_slice($values, 0, $targetIndex + 1);
        $avg6 = ! empty(array_slice($effective, -6)) ? array_sum(array_slice($effective, -6)) / count(array_slice($effective, -6)) : 0;
        $avg12 = ! empty(array_slice($effective, -12)) ? array_sum(array_slice($effective, -12)) / count(array_slice($effective, -12)) : 0;
        $month = (int) Carbon::parse($targetKey.'-01')->format('n');
        $trend = $this->trend(array_slice($effective, -3));
        $stock = $this->getCurrentStock($faskes->id, $obat->id);
        $tipe = $faskes->tipe === 'puskesmas' ? 1.0 : 0.0;

        return [(float) $lag1, (float) $lag2, (float) $lag3, (float) $avg6, (float) $avg12, (float) $month, (float) $trend, (float) $stock, $tipe];
    }

    private function getFeatureNames(): array
    {
        return ['lag_1_bulan', 'lag_2_bulan', 'lag_3_bulan', 'rata_rata_6_bulan', 'rata_rata_12_bulan', 'bulan', 'trend_3_bulan', 'stok_saat_ini', 'tipe_faskes'];
    }

    private function trend(array $v): float
    {
        $c = count($v);
        if ($c < 2) {
            return 0;
        }
        $xMean = ($c - 1) / 2;
        $yMean = array_sum($v) / $c;
        $num = 0;
        $den = 0;
        for ($i = 0; $i < $c; $i++) {
            $dx = $i - $xMean;
            $num += $dx * ($v[$i] - $yMean);
            $den += $dx ** 2;
        }

        return $den == 0 ? 0 : $num / $den;
    }

    private function stdDev(array $v): float
    {
        $c = count($v);
        if ($c <= 1) {
            return 0;
        }
        $m = array_sum($v) / $c;
        $var = array_sum(array_map(fn ($x) => ($x - $m) ** 2, $v)) / ($c - 1);

        return sqrt($var);
    }

    private function rSquared(array $pred, array $true): float
    {
        $mean = array_sum($true) / max(1, count($true));
        $ssTot = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $true));
        if ($ssTot == 0) {
            return 0;
        }
        $ssRes = 0;
        foreach ($pred as $i => $p) {
            $ssRes += ($true[$i] - $p) ** 2;
        }

        return 1 - $ssRes / $ssTot;
    }

    private function mae(array $pred, array $true): float
    {
        $s = 0;
        foreach ($pred as $i => $p) {
            $s += abs($true[$i] - $p);
        }

        return $s / max(1, count($true));
    }

    private function mape(array $pred, array $true): ?float
    {
        $s = 0;
        $cnt = 0;
        foreach ($pred as $i => $p) {
            if ($true[$i] == 0) {
                continue;
            }
            $s += abs(($true[$i] - $p) / $true[$i]);
            $cnt++;
        }

        return $cnt > 0 ? $s / $cnt * 100 : null;
    }

    private function getCurrentStock(int $fid, int $oid): int
    {
        $s = StokFaskes::query()->where('fasilitas_id', $fid)->where('obat_id', $oid)->value('jumlah');
        if ($s !== null) {
            return (int) $s;
        }
        $b = BatchStok::query()->where('obat_id', $oid)->where('status', 'tersedia')->where('jumlah', '>', 0)->where('fasilitas_id', $fid)->sum('jumlah');
        if ($b > 0) {
            return (int) $b;
        }
        $faskes = FasilitasKesehatan::find($fid);
        if ($faskes?->tipe === 'gudang') {
            $g = StokGudang::query()->where('obat_id', $oid)->value('jumlah');
            if ($g !== null) {
                return (int) $g;
            }

            return (int) BatchStok::query()->where('obat_id', $oid)->whereNull('fasilitas_id')->where('status', 'tersedia')->sum('jumlah');
        }

        return 0;
    }

    private function saveModelFile(int $fid, int $oid, AnnModel $model): string
    {
        $path = "ai-models/{$fid}_{$oid}.json";
        Storage::disk('local')->put($path, json_encode($model->toArray()));

        return $path;
    }

    private function loadModel(ModelPrediksi $m): AnnModel
    {
        if ($m->model_path && Storage::disk('local')->exists($m->model_path)) {
            return AnnModel::fromArray(json_decode(Storage::disk('local')->get($m->model_path), true));
        }
        if (! blank($m->model_data)) {
            $arr = json_decode($m->model_data, true);
            if (is_array($arr) && isset($arr['weights'])) {
                return AnnModel::fromArray($arr);
            }
        }
        throw new \RuntimeException('Model not found');
    }

    private function savePrediction(?ModelPrediksi $model, int $fid, int $oid, int $bulan, int $tahun, int $jumlah, int $cl, int $cu, string $metode): PrediksiKebutuhan
    {
        return PrediksiKebutuhan::updateOrCreate(['fasilitas_id' => $fid, 'obat_id' => $oid, 'periode_bulan' => $bulan, 'periode_tahun' => $tahun], ['model_id' => $model?->id, 'jumlah_prediksi' => $jumlah, 'confidence_lower' => $cl, 'confidence_upper' => $cu, 'metode' => $metode, 'dibuat_oleh' => null]);
    }

    private function createInsufficientDataModel(FasilitasKesehatan $f, Obat $o, int $c): ModelPrediksi
    {
        return ModelPrediksi::updateOrCreate(['fasilitas_id' => $f->id, 'obat_id' => $o->id], ['model_data' => '', 'model_path' => null, 'akurasi_r2' => null, 'mae' => null, 'mape' => null, 'tanggal_training' => now(), 'data_training_count' => $c, 'fitur_digunakan' => null, 'status' => 'data_belum_cukup', 'error_message' => null]);
    }

    private function createFailedModel(FasilitasKesehatan $f, Obat $o, int $c, string $msg): ModelPrediksi
    {
        return ModelPrediksi::updateOrCreate(['fasilitas_id' => $f->id, 'obat_id' => $o->id], ['model_data' => '', 'model_path' => null, 'akurasi_r2' => null, 'mae' => null, 'mape' => null, 'tanggal_training' => now(), 'data_training_count' => $c, 'fitur_digunakan' => null, 'status' => 'gagal', 'error_message' => $msg]);
    }
}
