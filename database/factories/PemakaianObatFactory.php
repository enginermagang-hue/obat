<?php

namespace Database\Factories;

use App\Models\DetailPemakaianObat;
use App\Models\FasilitasKesehatan;
use App\Models\PemakaianObat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PemakaianObat>
 */
class PemakaianObatFactory extends Factory
{
    protected $model = PemakaianObat::class;

    public function definition(): array
    {
        return [
            'nomor_pemakaian' => 'PMK-'.now()->format('Ym').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'fasilitas_id' => FasilitasKesehatan::factory(),
            'tanggal_pemakaian' => $this->faker->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'jenis_pelayanan' => $this->faker->randomElement([
                'rawat_jalan', 'rawat_inap', 'uks', 'posyandu',
                'pusling', 'gigi', 'laboratorium', 'apotek', 'lainnya',
            ]),
            'nama_pasien' => $this->faker->name(),
            'no_rekam_medis' => 'RM-'.now()->format('Ymd').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'diagnosa_kode' => $this->faker->optional(0.7)->regexify('[A-Z][0-9]{2}'),
            'user_id' => User::factory(),
            'catatan' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function rawatJalan(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'rawat_jalan']);
    }

    public function rawatInap(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'rawat_inap']);
    }

    public function uks(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'uks']);
    }

    public function posyandu(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'posyandu']);
    }

    public function pusling(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'pusling']);
    }

    public function gigi(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'gigi']);
    }

    public function laboratorium(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'laboratorium']);
    }

    public function apotek(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'apotek']);
    }

    public function lainnya(): static
    {
        return $this->state(fn (): array => ['jenis_pelayanan' => 'lainnya']);
    }

    public function denganDiagnosa(string $kode): static
    {
        return $this->state(fn (): array => ['diagnosa_kode' => $kode]);
    }

    public function hariIni(): static
    {
        return $this->state(fn (): array => [
            'tanggal_pemakaian' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Attach N detail rows (random obats + random batches).
     */
    public function withDetails(int $count = 2): static
    {
        return $this->has(DetailPemakaianObat::factory()->count($count), 'details');
    }
}
