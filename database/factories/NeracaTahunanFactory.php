<?php

namespace Database\Factories;

use App\Models\NeracaTahunan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NeracaTahunan>
 */
class NeracaTahunanFactory extends Factory
{
    protected $model = NeracaTahunan::class;

    public function definition(): array
    {
        return [
            'nomor_neraca' => 'NR-'.$this->faker->unique()->numerify('#####'),
            'fasilitas_id' => null,
            'tahun' => (int) date('Y'),
            'status' => 'draft',
            'catatan' => null,
            'dibuat_oleh' => User::factory(),
        ];
    }
}
