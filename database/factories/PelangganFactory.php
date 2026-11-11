<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    public function definition(): array
    {
        return [
            'nama_pelanggan' => substr(fake()->name(), 0, 100),
            'kota' => substr(fake()->city(), 0, 50),
            'no_hp' => fake()->unique()->numerify('08##########'),
            'alamat' => fake()->address(),
        ];
    }
}
