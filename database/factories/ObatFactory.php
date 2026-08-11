<?php

namespace Database\Factories;

use App\Models\Obat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Obat>
 */
class ObatFactory extends Factory
{
    protected $model = Obat::class;

    public function definition(): array
    {
        return [
            'kode_obat' => 'OBT-'.$this->faker->unique()->numerify('#####'),
            'nama_obat' => $this->faker->word().' '.$this->faker->word(),
            'nama_generik' => $this->faker->word(),
            'kategori' => $this->faker->randomElement(['Analgesik', 'Antibiotik', 'Vitamin', 'Kardiovaskular', 'Pencernaan', 'Antiinfeksi']),
            'satuan' => $this->faker->randomElement(['Tablet', 'Kapsul', 'Botol', 'Ampul', 'Tube', 'Sachet']),
            'kekuatan' => $this->faker->randomElement(['500 mg', '250 mg', '100 mg']),
            'bentuk_sediaan' => $this->faker->randomElement(['tablet', 'kapsul', 'sirup', 'salep', 'injeksi']),
            'produsen' => $this->faker->company(),
            'kemasan' => $this->faker->randomElement(['Box @ 100', 'Botol 60 ml', 'Strip @ 10']),
            'harga_satuan' => $this->faker->randomFloat(2, 100, 50000),
            'status' => 'aktif',
            'metode_stok' => $this->faker->randomElement(['fefo', 'fifo', 'lifo']),
        ];
    }
}
