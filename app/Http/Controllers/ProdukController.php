<?php

namespace App\Http\Controllers;

use App\Http\Requests\Produk\StoreProdukRequest;
use App\Http\Requests\Produk\UpdateProdukRequest;
use App\Http\Resources\KategoriResource;
use App\Http\Resources\ProdukResource;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\ProdukService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProdukController extends Controller
{
    public function __construct(
        private readonly ProdukService $produkService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Produk::class);

        $search = $request->string('search')->toString();
        $kategoriId = $request->integer('kategori') ?: null;
        $status = $request->string('status')->toString();

        $products = Produk::query()
            ->with('kategori')
            ->search($search)
            ->when($kategoriId, fn ($q) => $q->where('id_kategori', $kategoriId))
            ->when($status === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($status === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when($status === 'menipis', fn ($q) => $q->whereBetween('stok', [1, 5]))
            ->when($status === 'habis', fn ($q) => $q->where('stok', '<=', 0))
            ->latest('id_produk')
            ->paginate(15)
            ->withQueryString();

        $categories = Kategori::query()
            ->active()
            ->orderBy('nama_kategori')
            ->get();

        return Inertia::render('Produk/Index', [
            'products' => ProdukResource::collection($products),
            'categories' => KategoriResource::collection($categories),
            'filters' => [
                'search' => $search,
                'kategori' => $kategoriId,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Produk::class);

        $categories = Kategori::query()
            ->active()
            ->orderBy('nama_kategori')
            ->get();

        return Inertia::render('Produk/Create', [
            'categories' => KategoriResource::collection($categories),
        ]);
    }

    public function store(StoreProdukRequest $request): RedirectResponse
    {
        $this->produkService->create(
            $request->validated(),
            $request->file('gambar'),
        );

        return redirect()
            ->route('produk.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Request $request, Produk $produk): Response
    {
        $this->authorize('view', $produk);

        $produk->load('kategori');

        $salesStats = null;

        if ($request->user()?->isAdmin()) {
            $stats = DB::table('tbl_detail_transaksi as d')
                ->join('tbl_transaksi as t', 'd.id_transaksi', '=', 't.id_transaksi')
                ->where(function ($q) use ($produk) {
                    $q->where('d.id_produk', $produk->id_produk)
                        ->orWhere(function ($legacy) use ($produk) {
                            $legacy->whereNull('d.id_produk')
                                ->where('d.nama_produk', $produk->nama_produk);
                        });
                })
                ->where(function ($q) {
                    $q->where('t.status', '!=', Transaksi::STATUS_DIBATALKAN)
                        ->orWhereNull('t.status');
                })
                ->selectRaw('COALESCE(SUM(d.jumlah), 0) as total_terjual, COALESCE(SUM(d.jumlah * d.harga), 0) as total_pendapatan, COUNT(DISTINCT d.id_transaksi) as total_transaksi')
                ->first();

            $salesStats = [
                'total_terjual' => (int) ($stats->total_terjual ?? 0),
                'total_pendapatan' => (float) ($stats->total_pendapatan ?? 0),
                'total_transaksi' => (int) ($stats->total_transaksi ?? 0),
            ];
        }

        return Inertia::render('Produk/Show', [
            'product' => ProdukResource::make($produk),
            'salesStats' => $salesStats,
        ]);
    }

    public function edit(Produk $produk): Response
    {
        $this->authorize('update', $produk);

        $produk->load('kategori');
        $categories = Kategori::query()
            ->orderBy('nama_kategori')
            ->get();

        return Inertia::render('Produk/Edit', [
            'product' => ProdukResource::make($produk),
            'categories' => KategoriResource::collection($categories),
        ]);
    }

    public function update(UpdateProdukRequest $request, Produk $produk): RedirectResponse
    {
        $this->produkService->update(
            $produk,
            $request->validated(),
            $request->file('gambar'),
        );

        return redirect()
            ->route('produk.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $this->authorize('delete', $produk);

        $this->produkService->delete($produk);

        return redirect()
            ->route('produk.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
