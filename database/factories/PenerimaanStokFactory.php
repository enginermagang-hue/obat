<?php

namespace Database\Factories;

use App\Models\PenerimaanStok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenerimaanStok>
 */
class PenerimaanStokFactory extends Factory
{
    protected $model = PenerimaanStok::class;

    public function definition(): array
    {
        return [
            'nomor_penerimaan' => null,
            'tipe' => 'pembelian',
            'supplier_id' => null,
            'nomor_po' => null,
            'nomor_invoice' => null,
            'tanggal_penerimaan' => now(),
            'fasilitas_id' => null,
            'user_id' => User::factory(),
            'status' => 'dikonfirmasi',
            'catatan' => null,
            'total_biaya' => 0,
        ];
    }
}
