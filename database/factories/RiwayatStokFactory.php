<?php

namespace Database\Factories;

use App\Models\Obat;
use App\Models\RiwayatStok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatStok>
 */
class RiwayatStokFactory extends Factory
{
    protected $model = RiwayatStok::class;

    public function definition(): array
    {
        $jumlah = $this->faker->numberBetween(1, 200);

        return [
            'fasilitas_id' => null,
            'obat_id' => Obat::factory(),
            'tipe' => 'masuk',
            'jumlah' => $jumlah,
            'stok_sebelum' => 0,
            'stok_sesudah' => $jumlah,
            'referensi_type' => null,
            'referensi_id' => null,
            'user_id' => User::factory(),
            'keterangan' => null,
            'tanggal' => now(),
        ];
    }
}
