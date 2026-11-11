<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $cabangUtama = Cabang::query()->orderBy('id_cabang')->first();

        User::query()->firstOrCreate(
            ['email' => 'admin@toko.test'],
            [
                'name' => 'Admin Toko',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'id_cabang' => $cabangUtama?->id_cabang,
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'kasir@toko.test'],
            [
                'name' => 'Kasir Utama',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => User::ROLE_KASIR,
                'id_cabang' => $cabangUtama?->id_cabang,
                'is_active' => true,
            ]
        );
    }
}
