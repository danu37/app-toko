<?php

namespace App\Services;

use App\Models\DetailTransaksi;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransaksiService
{
    public function __construct(
        private readonly ImageCompressionService $imageCompressionService,
    ) {}

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
            $subtotalBarang = 0.0;

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
                $lineSubtotal = round($hargaSatuan * $jumlah, 2);
                $subtotalBarang = round($subtotalBarang + $lineSubtotal, 2);

                $detailPayloads[] = [
                    'index' => $index,
                    'id_produk' => $produk->id_produk,
                    'barcode' => $produk->barcode,
                    'nama_produk' => $produk->nama_produk,
                    'jumlah' => $jumlah,
                    'harga' => $hargaSatuan,
                ];
            }

            $ppnRate = Transaksi::PPN_RATE;
            $pajak = round($subtotalBarang * ($ppnRate / 100), 2);
            $totalBelanja = round($subtotalBarang + $pajak, 2);

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
                'subtotal' => $subtotalBarang,
                'ppn_rate' => $ppnRate,
                'pajak' => $pajak,
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
                    'barcode' => $detail['barcode'],
                    'nama_produk' => $detail['nama_produk'],
                    'jumlah' => $detail['jumlah'],
                    'harga' => $detail['harga'],
                ]);
            }

            return $transaksi->load(['pelanggan', 'cabang', 'user', 'detail.produk']);
        });
    }

    /**
     * Menghapus transaksi beserta detailnya secara atomik dan mengembalikan stok produk
     * jika transaksi berstatus non-batal.
     */
    public function delete(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi): void {
            $transaksi->loadMissing('detail');

            if ($transaksi->status !== Transaksi::STATUS_DIBATALKAN) {
                foreach ($transaksi->detail as $detail) {
                    if ($detail->id_produk && (int) $detail->jumlah > 0) {
                        Produk::query()
                            ->where('id_produk', $detail->id_produk)
                            ->increment('stok', (int) $detail->jumlah);
                    }
                }
            }

            $transaksi->detail()->delete();
            $transaksi->delete();
        });
    }

    /**
     * Membuat pesanan pembelian oleh pelanggan beserta upload bukti pembayaran.
     * Status awal: menunggu_verifikasi sampai diverifikasi oleh Kasir atau Admin.
     */
    public function createCustomerOrder(array $payload, User $customerUser, UploadedFile $proofFile): Transaksi
    {
        $storedProofPath = $this->imageCompressionService->store($proofFile, 'bukti-pembayaran');

        return DB::transaction(function () use ($payload, $customerUser, $storedProofPath): Transaksi {
            if (! $customerUser->id_pelanggan) {
                $pelanggan = Pelanggan::create([
                    'nama_pelanggan' => $customerUser->name,
                ]);
                $customerUser->update(['id_pelanggan' => $pelanggan->id_pelanggan]);
            }

            $requestedItems = collect($payload['items']);
            $productIds = $requestedItems->pluck('id_produk')->map(fn ($id) => (int) $id)->unique()->sort()->values();

            $products = Produk::query()
                ->whereIn('id_produk', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_produk');

            $detailPayloads = [];
            $subtotalBarang = 0.0;

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
                $lineSubtotal = round($hargaSatuan * $jumlah, 2);
                $subtotalBarang = round($subtotalBarang + $lineSubtotal, 2);

                $detailPayloads[] = [
                    'index' => $index,
                    'id_produk' => $produk->id_produk,
                    'barcode' => $produk->barcode,
                    'nama_produk' => $produk->nama_produk,
                    'jumlah' => $jumlah,
                    'harga' => $hargaSatuan,
                ];
            }

            $ppnRate = Transaksi::PPN_RATE;
            $pajak = round($subtotalBarang * ($ppnRate / 100), 2);
            $totalBelanja = round($subtotalBarang + $pajak, 2);

            $transaksi = Transaksi::create([
                'kode_transaksi' => $this->generateKodeTransaksi(),
                'tanggal' => now()->toDateString(),
                'id_pelanggan' => (int) $customerUser->id_pelanggan,
                'id_cabang' => $payload['id_cabang'] ?? null,
                'user_id' => null,
                'nama_kasir' => 'Menunggu Verifikasi',
                'subtotal' => $subtotalBarang,
                'ppn_rate' => $ppnRate,
                'pajak' => $pajak,
                'total_belanja' => $totalBelanja,
                'metode_pembayaran' => $payload['metode_pembayaran'],
                'bukti_pembayaran' => $storedProofPath,
                'uang_bayar' => $totalBelanja,
                'kembalian' => 0.0,
                'status' => Transaksi::STATUS_MENUNGGU_VERIFIKASI,
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
                        "items.{$itemIndex}.jumlah" => "Stok {$detail['nama_produk']} berubah dan tidak mencukupi.",
                    ]);
                }

                DetailTransaksi::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $detail['id_produk'],
                    'barcode' => $detail['barcode'],
                    'nama_produk' => $detail['nama_produk'],
                    'jumlah' => $detail['jumlah'],
                    'harga' => $detail['harga'],
                ]);
            }

            return $transaksi->load(['pelanggan', 'cabang', 'detail.produk']);
        });
    }

    /**
     * Verifikasi (setujui atau tolak) bukti pembayaran transaksi pelanggan oleh Kasir atau Admin.
     */
    public function verifyPayment(Transaksi $transaksi, User $verifier, string $decision, ?string $note = null): Transaksi
    {
        if ($transaksi->status !== Transaksi::STATUS_MENUNGGU_VERIFIKASI) {
            throw ValidationException::withMessages([
                'status' => 'Transaksi ini sudah diverifikasi sebelumnya atau tidak berstatus menunggu verifikasi.',
            ]);
        }

        return DB::transaction(function () use ($transaksi, $verifier, $decision, $note): Transaksi {
            $transaksi->loadMissing('detail');

            if ($decision === 'approve') {
                $transaksi->update([
                    'status' => Transaksi::STATUS_SELESAI,
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'catatan_verifikasi' => $note,
                    'user_id' => $transaksi->user_id ?? $verifier->id,
                    'nama_kasir' => $verifier->name,
                ]);
            } else {
                foreach ($transaksi->detail as $detail) {
                    if ($detail->id_produk && (int) $detail->jumlah > 0) {
                        Produk::query()
                            ->where('id_produk', $detail->id_produk)
                            ->increment('stok', (int) $detail->jumlah);
                    }
                }

                $transaksi->update([
                    'status' => Transaksi::STATUS_DIBATALKAN,
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'catatan_verifikasi' => $note ?: 'Bukti pembayaran ditolak oleh petugas.',
                ]);
            }

            return $transaksi->fresh(['pelanggan', 'cabang', 'user', 'verifier', 'detail.produk']);
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
