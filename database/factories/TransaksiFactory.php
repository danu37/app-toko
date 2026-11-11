<?php

namespace Database\Factories;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaksi>
 */
class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    public function definition(): array
    {
        return [
            'kode_transaksi' => 'TRX-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->bothify('??????')),
            'tanggal' => now()->toDateString(),
            'id_pelanggan' => Pelanggan::factory(),
            'id_cabang' => Cabang::factory(),
            'user_id' => User::factory(),
            'nama_kasir' => null,
            'total_belanja' => fake()->randomFloat(2, 10000, 500000),
            'metode_pembayaran' => 'tunai',
            'status' => Transaksi::STATUS_SELESAI,
            'catatan' => null,
        ];
    }
}
