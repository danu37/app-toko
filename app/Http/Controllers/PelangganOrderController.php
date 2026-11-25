<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pelanggan\StorePelangganOrderRequest;
use App\Http\Resources\CabangResource;
use App\Http\Resources\ProdukResource;
use App\Models\Cabang;
use App\Models\Produk;
use App\Services\TransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PelangganOrderController extends Controller
{
    public function __construct(
        private readonly TransaksiService $transaksiService,
    ) {}

    public function create(Request $request): Response
    {
        $products = Produk::query()
            ->active()
            ->where('stok', '>', 0)
            ->with('kategori:id_kategori,nama_kategori,slug')
            ->orderBy('nama_produk')
            ->get([
                'id_produk',
                'id_kategori',
                'kode_produk',
                'nama_produk',
                'slug',
                'harga',
                'stok',
                'gambar',
                'is_featured',
                'is_active',
            ]);

        $branches = Cabang::query()
            ->orderBy('nama_cabang')
            ->get(['id_cabang', 'nama_cabang', 'kota']);

        return Inertia::render('Pelanggan/Order', [
            'products' => ProdukResource::collection($products),
            'branches' => CabangResource::collection($branches),
            'preselectedProductId' => $request->integer('produk') ?: null,
            'qrisInfo' => config('store.qris'),
        ]);
    }

    public function store(StorePelangganOrderRequest $request): RedirectResponse
    {
        $transaksi = $this->transaksiService->createCustomerOrder(
            $request->validated(),
            $request->user(),
            $request->file('bukti_pembayaran'),
        );

        return redirect()
            ->route('pelanggan.dashboard')
            ->with('success', "Pesanan {$transaksi->kode_transaksi} berhasil dikirim dan menunggu verifikasi Kasir/Admin.");
    }
}
