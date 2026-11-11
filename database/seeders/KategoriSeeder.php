<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'nama_kategori' => 'Sembako',
                'slug' => 'sembako',
                'deskripsi' => 'Kebutuhan pokok harian seperti beras, minyak goreng, gula, dan telur.',
                'is_active' => true,
            ],
            [
                'nama_kategori' => 'Makanan Instan',
                'slug' => 'makanan-instan',
                'deskripsi' => 'Mi instan, makanan siap saji, dan camilan.',
                'is_active' => true,
            ],
            [
                'nama_kategori' => 'Minuman',
                'slug' => 'minuman',
                'deskripsi' => 'Air mineral, teh, kopi, dan minuman kemasan.',
                'is_active' => true,
            ],
            [
                'nama_kategori' => 'Kebutuhan Rumah',
                'slug' => 'kebutuhan-rumah',
                'deskripsi' => 'Perlengkapan kebersihan dan keperluan rumah tangga.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Kategori::query()->firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
