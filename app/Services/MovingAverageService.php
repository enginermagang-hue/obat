<?php

namespace App\Services;

class MovingAverageService
{
    /**
     * Calculate moving average prediction for the next period.
     *
     * @param  array<int>  $monthlyData  Array of monthly totals (key = period label, value = amount)
     * @param  int  $window  Number of months to average
     * @return array{jumlah: int, confidence_lower: int, confidence_upper: int}
     */
    public function predict(array $monthlyData, int $window = 3): array
    {
        $values = array_values($monthlyData);

        if (empty($values)) {
            return [
                'jumlah' => 0,
                'confidence_lower' => 0,
                'confidence_upper' => 0,
            ];
        }

        // Use last N values for moving average
        $recent = array_slice($values, -$window);
        $average = (int) round(array_sum($recent) / count($recent));

        // 95% confidence interval: ±1.96 * SD (sample)
        $variance = $this->variance($recent, $average);
        $stdDev = sqrt($variance);
        $margin = (int) round(1.96 * $stdDev);

        return [
            'jumlah' => max(0, $average),
            'confidence_lower' => max(0, $average - $margin),
            'confidence_upper' => $average + $margin,
        ];
    }

    /**
     * Check if there is enough data for a meaningful prediction.
     */
    public function hasSufficientData(array $monthlyData, int $minimumMonths = 3): bool
    {
        return count($monthlyData) >= $minimumMonths;
    }

    /**
     * Calculate variance for a set of values.
     */
    private function variance(array $values, float $mean): float
    {
        $count = count($values);

        if ($count <= 1) {
            return 0;
        }

        $squaredDiffs = array_map(fn ($v): float => ($v - $mean) ** 2, $values);

        return array_sum($squaredDiffs) / ($count - 1);
    }
}
