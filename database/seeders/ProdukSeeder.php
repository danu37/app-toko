<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $sembako = Kategori::query()->where('slug', 'sembako')->first();
        $instan = Kategori::query()->where('slug', 'makanan-instan')->first();
        $minuman = Kategori::query()->where('slug', 'minuman')->first();
        $rumah = Kategori::query()->where('slug', 'kebutuhan-rumah')->first();

        $products = [
            [
                'id_kategori' => $sembako?->id_kategori,
                'kode_produk' => 'PRD-001',
                'nama_produk' => 'Beras 5 Kg',
                'slug' => 'beras-5-kg',
                'harga' => 75000.00,
                'stok' => 40,
                'deskripsi' => 'Beras putih pulen kualitas premium kemasan 5 kilogram.',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'id_kategori' => $sembako?->id_kategori,
                'kode_produk' => 'PRD-002',
                'nama_produk' => 'Minyak Goreng',
                'slug' => 'minyak-goreng',
                'harga' => 25000.00,
                'stok' => 60,
                'deskripsi' => 'Minyak goreng kelapa sawit kemasan pouch 1 liter.',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'id_kategori' => $sembako?->id_kategori,
                'kode_produk' => 'PRD-003',
                'nama_produk' => 'Gula Pasir',
                'slug' => 'gula-pasir',
                'harga' => 15000.00,
                'stok' => 80,
                'deskripsi' => 'Gula pasir putih kristal bersih kemasan 1 kilogram.',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'id_kategori' => $sembako?->id_kategori,
                'kode_produk' => 'PRD-004',
                'nama_produk' => 'Telur 1 Kg',
                'slug' => 'telur-1-kg',
                'harga' => 28000.00,
                'stok' => 35,
                'deskripsi' => 'Telur ayam negeri segar pilihan per 1 kilogram.',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'id_kategori' => $instan?->id_kategori,
                'kode_produk' => 'PRD-005',
                'nama_produk' => 'Mie Instan',
                'slug' => 'mie-instan',
                'harga' => 3500.00,
                'stok' => 200,
                'deskripsi' => 'Mi instan goreng rasa original praktis siap saji.',
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'id_kategori' => $minuman?->id_kategori,
                'kode_produk' => 'PRD-006',
                'nama_produk' => 'Kopi Bubuk Robusta 200g',
                'slug' => 'kopi-bubuk-robusta-200g',
                'harga' => 22000.00,
                'stok' => 45,
                'deskripsi' => 'Kopi bubuk hitam murni aroma mantap kemasan 200 gram.',
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'id_kategori' => $minuman?->id_kategori,
                'kode_produk' => 'PRD-007',
                'nama_produk' => 'Teh Celup Kotak 25s',
                'slug' => 'teh-celup-kotak-25s',
                'harga' => 8500.00,
                'stok' => 70,
                'deskripsi' => 'Teh hitam celup isi 25 kantong.',
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'id_kategori' => $rumah?->id_kategori,
                'kode_produk' => 'PRD-008',
                'nama_produk' => 'Sabun Cuci Piring 750ml',
                'slug' => 'sabun-cuci-piring-750ml',
                'harga' => 16500.00,
                'stok' => 50,
                'deskripsi' => 'Cairan pencuci piring ekstrak jeruk nipis kemasan 750ml.',
                'is_featured' => true,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Produk::query()->firstOrCreate(
                ['slug' => $product['slug']],
                $product
            );
        }
    }
}
