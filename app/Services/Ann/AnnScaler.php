<?php

namespace App\Services\Ann;

class AnnScaler
{
    /** @var array<float> */
    public array $means = [];

    /** @var array<float> */
    public array $stds = [];

    /**
     * @param  array<array<float>>  $X
     */
    public function fit(array $X): void
    {
        if (empty($X)) {
            return;
        }
        $n = count($X);
        $d = count($X[0]);
        for ($j = 0; $j < $d; $j++) {
            $col = array_column($X, $j);
            $mean = array_sum($col) / $n;
            $var = 0;
            foreach ($col as $v) {
                $var += ($v - $mean) ** 2;
            }
            $var /= max(1, $n);
            $std = sqrt($var);
            $this->means[$j] = $mean;
            $this->stds[$j] = $std < 1e-8 ? 1.0 : $std;
        }
    }

    /**
     * @param  array<float>  $x
     * @return array<float>
     */
    public function transformRow(array $x): array
    {
        $out = [];
        foreach ($x as $j => $v) {
            $out[$j] = ($v - ($this->means[$j] ?? 0)) / ($this->stds[$j] ?? 1);
        }

        return $out;
    }

    /**
     * @param  array<array<float>>  $X
     * @return array<array<float>>
     */
    public function transform(array $X): array
    {
        return array_map(fn ($r) => $this->transformRow($r), $X);
    }

    public function toArray(): array
    {
        return ['means' => $this->means, 'stds' => $this->stds];
    }

    public static function fromArray(array $data): self
    {
        $s = new self;
        $s->means = $data['means'] ?? [];
        $s->stds = $data['stds'] ?? [];

        return $s;
    }
}
