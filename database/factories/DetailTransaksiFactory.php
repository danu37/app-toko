<?php

namespace Database\Factories;

use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailTransaksi>
 */
class DetailTransaksiFactory extends Factory
{
    protected $model = DetailTransaksi::class;

    public function definition(): array
    {
        return [
            'id_transaksi' => Transaksi::factory(),
            'id_produk' => Produk::factory(),
            'nama_produk' => function (array $attributes): string {
                if (! empty($attributes['id_produk'])) {
                    return Produk::query()->find($attributes['id_produk'])?->nama_produk
                        ?? fake()->words(2, true);
                }

                return fake()->words(2, true);
            },
            'jumlah' => fake()->numberBetween(1, 5),
            'harga' => function (array $attributes): float {
                if (! empty($attributes['id_produk'])) {
                    return (float) (Produk::query()->find($attributes['id_produk'])?->harga ?? 10000);
                }

                return (float) fake()->randomFloat(2, 5000, 150000);
            },
        ];
    }
}
