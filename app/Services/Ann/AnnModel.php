<?php

namespace App\Services\Ann;

class AnnModel
{
    /**
     * @param  array<array<array<float>>>  $weights  per layer [out][in]
     * @param  array<array<float>>  $biases  per layer [out]
     * @param  array<int>  $layers  e.g. [9,12,8,1]
     */
    public function __construct(
        public array $weights,
        public array $biases,
        public array $layers,
        public AnnScaler $scaler,
        public float $yMean = 0,
        public float $yStd = 1,
    ) {}

    /**
     * @param  array<float>  $x  raw features
     */
    public function predict(array $x): float
    {
        $h = $this->scaler->transformRow($x);
        $numLayers = count($this->weights);
        for ($l = 0; $l < $numLayers; $l++) {
            $next = [];
            $w = $this->weights[$l];
            $b = $this->biases[$l];
            $isLast = $l === $numLayers - 1;
            foreach ($w as $o => $row) {
                $sum = $b[$o] ?? 0;
                foreach ($row as $j => $wv) {
                    $sum += $wv * ($h[$j] ?? 0);
                }
                $next[$o] = $isLast ? $sum : max(0, $sum); // ReLU hidden, linear output
            }
            $h = $next;
        }
        $raw = $h[0] ?? 0;

        return $raw * $this->yStd + $this->yMean;
    }

    public function toArray(): array
    {
        return [
            'weights' => $this->weights,
            'biases' => $this->biases,
            'layers' => $this->layers,
            'scaler' => $this->scaler->toArray(),
            'yMean' => $this->yMean,
            'yStd' => $this->yStd,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['weights'],
            $data['biases'],
            $data['layers'],
            AnnScaler::fromArray($data['scaler'] ?? []),
            $data['yMean'] ?? 0,
            $data['yStd'] ?? 1,
        );
    }
}
