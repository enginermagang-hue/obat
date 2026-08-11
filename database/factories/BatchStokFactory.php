<?php

namespace Database\Factories;

use App\Models\BatchStok;
use App\Models\Obat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchStok>
 */
class BatchStokFactory extends Factory
{
    protected $model = BatchStok::class;

    public function definition(): array
    {
        return [
            'obat_id' => Obat::factory(),
            'fasilitas_id' => null,
            'batch_number' => strtoupper($this->faker->bothify('BCH-####')),
            'tanggal_expired' => $this->faker->dateTimeBetween('+1 month', '+3 years'),
            'jumlah' => $this->faker->numberBetween(10, 500),
            'status' => 'tersedia',
            'tanggal_masuk' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
