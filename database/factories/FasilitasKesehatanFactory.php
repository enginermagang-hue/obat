<?php

namespace Database\Factories;

use App\Models\FasilitasKesehatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FasilitasKesehatan>
 */
class FasilitasKesehatanFactory extends Factory
{
    protected $model = FasilitasKesehatan::class;

    public function definition(): array
    {
        return [
            'kode_faskes' => 'FK-'.$this->faker->unique()->numerify('#####'),
            'nama' => $this->faker->company().' Faskes',
            'tipe' => 'puskesmas',
            'puskesmas_induk_id' => null,
            'alamat' => $this->faker->address(),
            'pic' => $this->faker->name(),
            'kontak_pic' => $this->faker->phoneNumber(),
            'telepon' => $this->faker->phoneNumber(),
            'kepala_faskes' => $this->faker->name(),
            'status' => 'aktif',
        ];
    }
}
