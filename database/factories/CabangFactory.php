<?php

namespace Database\Factories;

use App\Models\Cabang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cabang>
 */
class CabangFactory extends Factory
{
    protected $model = Cabang::class;

    public function definition(): array
    {
        $kota = fake()->city();

        return [
            'nama_cabang' => 'Cabang '.$kota,
            'kota' => substr($kota, 0, 50),
        ];
    }
}
