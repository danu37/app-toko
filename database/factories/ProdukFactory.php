<?php

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        $nama = Str::title(fake()->unique()->words(3, true));

        return [
            'id_kategori' => Kategori::factory(),
            'kode_produk' => 'PRD-'.strtoupper(fake()->unique()->bothify('???###')),
            'nama_produk' => substr($nama, 0, 100),
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(100, 9999),
            'harga' => fake()->randomFloat(2, 2500, 250000),
            'stok' => fake()->numberBetween(5, 100),
            'deskripsi' => fake()->sentence(12),
            'gambar' => null,
            'is_featured' => false,
            'is_active' => true,
        ];
    }
}
