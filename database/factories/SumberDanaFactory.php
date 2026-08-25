<?php

namespace Database\Factories;

use App\Models\SumberDana;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SumberDana>
 */
class SumberDanaFactory extends Factory
{
    protected $model = SumberDana::class;

    public function definition(): array
    {
        return [
            'kode' => 'SD-'.$this->faker->unique()->numerify('####'),
            'nama' => $this->faker->words(2, true),
            'tahun' => (int) date('Y'),
            'total_anggaran' => $this->faker->randomFloat(2, 1000000, 100000000),
            'keterangan' => null,
            'status' => true,
        ];
    }
}
