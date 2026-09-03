<?php

namespace App\Services\Ann;

class AnnTrainer
{
    public function __construct(
        public array $hiddenLayers = [12, 8],
        public float $lr = 0.01,
        public int $epochs = 800,
        public float $l2 = 1e-4,
        public int $patience = 20,
    ) {}

    /**
     * @param  array<array<float>>  $X
     * @param  array<float>  $y
     */
    public function train(array $X, array $y): AnnModel
    {
        $n = count($X);
        $inDim = count($X[0]);
        $layers = array_merge([$inDim], $this->hiddenLayers, [1]);

        $scaler = new AnnScaler;
        $scaler->fit($X);
        $Xs = $scaler->transform($X);

        $yMean = array_sum($y) / max(1, $n);
        $var = 0;
        foreach ($y as $v) {
            $var += ($v - $yMean) ** 2;
        }
        $yStd = sqrt($var / max(1, $n));
        if ($yStd < 1e-8) {
            $yStd = 1.0;
        }
        $ys = array_map(fn ($v) => ($v - $yMean) / $yStd, $y);

        // init weights He
        $weights = [];
        $biases = [];
        for ($l = 0; $l < count($layers) - 1; $l++) {
            $fanIn = $layers[$l];
            $fanOut = $layers[$l + 1];
            $std = sqrt(2 / $fanIn);
            $w = [];
            $b = array_fill(0, $fanOut, 0.0);
            for ($o = 0; $o < $fanOut; $o++) {
                $row = [];
                for ($j = 0; $j < $fanIn; $j++) {
                    $row[$j] = $this->randn() * $std;
                }
                $w[$o] = $row;
            }
            $weights[$l] = $w;
            $biases[$l] = $b;
        }

        // simple holdout for early stopping (80/20 split already done by caller, but do internal 80/20 of training)
        $split = (int) floor($n * 0.8);
        $bestLoss = INF;
        $bestW = $weights;
        $bestB = $biases;
        $wait = 0;

        for ($epoch = 0; $epoch < $this->epochs; $epoch++) {
            // shuffle indices
            $indices = range(0, $n - 1);
            shuffle($indices);
            foreach ($indices as $idx) {
                [$activations, $zs] = $this->forward($Xs[$idx], $weights, $biases);
                $pred = $activations[count($activations) - 1][0];
                $error = $pred - $ys[$idx];
                [$gradW, $gradB] = $this->backward($activations, $zs, $weights, $error);
                // SGD update
                foreach ($weights as $l => &$wl) {
                    foreach ($wl as $o => &$row) {
                        foreach ($row as $j => &$wv) {
                            $wv -= $this->lr * ($gradW[$l][$o][$j] + $this->l2 * $wv);
                        }
                    }
                }
                foreach ($biases as $l => &$bl) {
                    foreach ($bl as $o => &$bv) {
                        $bv -= $this->lr * $gradB[$l][$o];
                    }
                }
                unset($wl, $row, $wv, $bl, $bv);
            }

            // validation loss
            if ($split < $n) {
                $valLoss = 0;
                for ($i = $split; $i < $n; $i++) {
                    [$acts] = $this->forward($Xs[$i], $weights, $biases);
                    $p = $acts[count($acts) - 1][0];
                    $valLoss += ($p - $ys[$i]) ** 2;
                }
                $valLoss /= max(1, $n - $split);
                if ($valLoss + 1e-9 < $bestLoss) {
                    $bestLoss = $valLoss;
                    $bestW = $weights;
                    $bestB = $biases;
                    $wait = 0;
                } else {
                    $wait++;
                    if ($wait >= $this->patience) {
                        break;
                    }
                }
            }
        }

        if ($bestLoss !== INF) {
            $weights = $bestW;
            $biases = $bestB;
        }

        return new AnnModel($weights, $biases, $layers, $scaler, $yMean, $yStd);
    }

    private function forward(array $x, array $weights, array $biases): array
    {
        $activations = [$x];
        $zs = [];
        $h = $x;
        foreach ($weights as $l => $w) {
            $b = $biases[$l];
            $isLast = $l === count($weights) - 1;
            $z = [];
            $next = [];
            foreach ($w as $o => $row) {
                $sum = $b[$o] ?? 0;
                foreach ($row as $j => $wv) {
                    $sum += $wv * ($h[$j] ?? 0);
                }
                $z[$o] = $sum;
                $next[$o] = $isLast ? $sum : max(0, $sum);
            }
            $zs[] = $z;
            $activations[] = $next;
            $h = $next;
        }

        return [$activations, $zs];
    }

    private function backward(array $acts, array $zs, array $weights, float $error): array
    {
        $L = count($weights);
        $deltas = array_fill(0, $L, []);
        // output layer linear: delta = error
        $deltas[$L - 1] = [$error];
        for ($l = $L - 2; $l >= 0; $l--) {
            $nextDelta = $deltas[$l + 1];
            $wNext = $weights[$l + 1];
            $z = $zs[$l];
            $curr = [];
            $currSize = count($z);
            for ($j = 0; $j < $currSize; $j++) {
                $sum = 0;
                foreach ($wNext as $o => $row) {
                    $sum += $row[$j] * ($nextDelta[$o] ?? 0);
                }
                $curr[$j] = $sum * ($z[$j] > 0 ? 1 : 0);
            }
            $deltas[$l] = $curr;
        }

        $gradW = [];
        $gradB = [];
        foreach ($weights as $l => $w) {
            $aPrev = $acts[$l];
            $d = $deltas[$l];
            $gw = [];
            foreach ($w as $o => $row) {
                $grow = [];
                foreach ($row as $j => $_) {
                    $grow[$j] = $d[$o] * ($aPrev[$j] ?? 0);
                }
                $gw[$o] = $grow;
            }
            $gradW[$l] = $gw;
            $gradB[$l] = $d;
        }

        return [$gradW, $gradB];
    }

    private function randn(): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        $u1 = max($u1, 1e-9);

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }
}
