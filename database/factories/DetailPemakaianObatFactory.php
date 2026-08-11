<?php

namespace Database\Factories;

use App\Models\BatchStok;
use App\Models\DetailPemakaianObat;
use App\Models\Obat;
use App\Models\PemakaianObat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailPemakaianObat>
 */
class DetailPemakaianObatFactory extends Factory
{
    protected $model = DetailPemakaianObat::class;

    public function definition(): array
    {
        return [
            'pemakaian_id' => PemakaianObat::factory(),
            'obat_id' => Obat::factory(),
            'batch_id' => null,
            'jumlah' => $this->faker->numberBetween(1, 20),
            'dosis' => $this->faker->optional(0.6)->randomElement([
                '1x1 sehari', '2x1 sehari', '3x1 sehari', '4x1 sehari',
                '1x2 sehari', '2x2 sehari', '3x2 sehari',
            ]),
            'satuan_dosis' => $this->faker->optional(0.6)->randomElement([
                'tablet', 'kapsul', 'sirup', 'tetes',
                'salep', 'ml', 'mg', 'pcs',
            ]),
            'catatan' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    public function denganBatch(): static
    {
        return $this->state(fn (): array => [
            'batch_id' => BatchStok::factory(),
        ]);
    }

    public function jumlah(int $jumlah): static
    {
        return $this->state(fn (): array => ['jumlah' => $jumlah]);
    }
}
