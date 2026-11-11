<?php

namespace App\Services;

use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransaksiService
{
    /**
     * Membuat transaksi baru beserta detail barang dalam satu database transaction.
     * Harga, subtotal item, total belanja, kembalian, validasi stok, dan pengurangan stok
     * diproses sepenuhnya di backend berdasarkan data master produk resmi.
     */
    public function create(array $payload, User $actor): Transaksi
    {
        return DB::transaction(function () use ($payload, $actor): Transaksi {
            $requestedItems = collect($payload['items']);
            $productIds = $requestedItems->pluck('id_produk')->map(fn ($id) => (int) $id)->unique()->sort()->values();

            $products = Produk::query()
                ->whereIn('id_produk', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_produk');

            $detailPayloads = [];
            $totalBelanja = 0.0;

            foreach ($requestedItems as $index => $item) {
                $produkId = (int) $item['id_produk'];
                $jumlah = (int) $item['jumlah'];

                /** @var Produk|null $produk */
                $produk = $products->get($produkId);

                if (! $produk) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id_produk" => 'Produk tidak ditemukan atau tidak aktif.',
                    ]);
                }

                if ((int) $produk->stok < $jumlah) {
                    throw ValidationException::withMessages([
                        "items.{$index}.jumlah" => "Stok {$produk->nama_produk} tidak mencukupi (tersisa {$produk->stok}, diminta {$jumlah}).",
                    ]);
                }

                $hargaSatuan = (float) $produk->harga;
                $subtotal = round($hargaSatuan * $jumlah, 2);
                $totalBelanja = round($totalBelanja + $subtotal, 2);

                $detailPayloads[] = [
                    'index' => $index,
                    'id_produk' => $produk->id_produk,
                    'nama_produk' => $produk->nama_produk,
                    'jumlah' => $jumlah,
                    'harga' => $hargaSatuan,
                ];
            }

            $metode = $payload['metode_pembayaran'] ?? 'tunai';
            $rawUangBayar = $payload['uang_bayar'] ?? null;

            if ($metode === 'tunai' && ($rawUangBayar === null || $rawUangBayar === '')) {
                throw ValidationException::withMessages([
                    'uang_bayar' => 'Nominal pembayaran wajib dimasukkan untuk transaksi tunai.',
                ]);
            }

            if ($rawUangBayar !== null && $rawUangBayar !== '') {
                $uangBayar = round((float) $rawUangBayar, 2);

                if ($uangBayar < $totalBelanja) {
                    throw ValidationException::withMessages([
                        'uang_bayar' => 'Uang pembayaran kurang dari total belanja (Rp '.number_format($totalBelanja, 0, ',', '.').').',
                    ]);
                }

                $kembalian = round($uangBayar - $totalBelanja, 2);
            } else {
                $uangBayar = $totalBelanja;
                $kembalian = 0.0;
            }

            $transaksi = Transaksi::create([
                'kode_transaksi' => $this->generateKodeTransaksi(),
                'tanggal' => $payload['tanggal'] ?? now()->toDateString(),
                'id_pelanggan' => (int) $payload['id_pelanggan'],
                'id_cabang' => $payload['id_cabang'] ?? $actor->id_cabang,
                'user_id' => $actor->id,
                'nama_kasir' => $actor->name,
                'total_belanja' => $totalBelanja,
                'metode_pembayaran' => $metode,
                'uang_bayar' => $uangBayar,
                'kembalian' => $kembalian,
                'status' => Transaksi::STATUS_SELESAI,
                'catatan' => $payload['catatan'] ?? null,
            ]);

            foreach ($detailPayloads as $detail) {
                $itemIndex = $detail['index'];

                $affected = Produk::query()
                    ->where('id_produk', $detail['id_produk'])
                    ->where('stok', '>=', $detail['jumlah'])
                    ->decrement('stok', $detail['jumlah']);

                if ($affected !== 1) {
                    throw ValidationException::withMessages([
                        "items.{$itemIndex}.jumlah" => "Stok {$detail['nama_produk']} berubah dan tidak mencukupi saat menyimpan transaksi.",
                    ]);
                }

                DetailTransaksi::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $detail['id_produk'],
                    'nama_produk' => $detail['nama_produk'],
                    'jumlah' => $detail['jumlah'],
                    'harga' => $detail['harga'],
                ]);
            }

            return $transaksi->load(['pelanggan', 'cabang', 'user', 'detail.produk']);
        });
    }

    private function generateKodeTransaksi(): string
    {
        do {
            $kode = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Transaksi::query()->where('kode_transaksi', $kode)->exists());

        return $kode;
    }
}
