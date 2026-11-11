<?php

namespace App\Http\Controllers;

use App\Http\Resources\KategoriResource;
use App\Http\Resources\ProdukResource;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        $categories = Kategori::query()
            ->active()
            ->withCount(['produk' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('nama_kategori')
            ->get();

        $featuredProducts = Produk::query()
            ->active()
            ->with('kategori')
            ->withSum('detailTransaksi as terjual_qty', 'jumlah')
            ->orderByDesc('is_featured')
            ->orderByDesc('terjual_qty')
            ->latest('id_produk')
            ->limit(8)
            ->get();

        $branches = Cabang::query()
            ->orderBy('id_cabang')
            ->get(['id_cabang', 'nama_cabang', 'kota']);

        return Inertia::render('Store/Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'categories' => KategoriResource::collection($categories),
            'featuredProducts' => ProdukResource::collection($featuredProducts),
            'branches' => $branches,
            'storeStats' => [
                'total_products' => Produk::query()->active()->count(),
                'total_categories' => $categories->count(),
                'total_branches' => $branches->count(),
            ],
        ]);
    }

    public function shop(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $categorySlug = $request->string('kategori')->toString();
        $sort = $request->string('sort')->toString();

        $products = Produk::query()
            ->active()
            ->with('kategori')
            ->search($search)
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('kategori', fn ($q) => $q->where('slug', $categorySlug));
            })
            ->when($sort === 'termurah', fn ($q) => $q->orderBy('harga'))
            ->when($sort === 'termahal', fn ($q) => $q->orderByDesc('harga'))
            ->when(! in_array($sort, ['termurah', 'termahal'], true), fn ($q) => $q->latest('id_produk'))
            ->paginate(12)
            ->withQueryString();

        $categories = Kategori::query()
            ->active()
            ->orderBy('nama_kategori')
            ->get();

        return Inertia::render('Store/Shop', [
            'products' => ProdukResource::collection($products),
            'categories' => KategoriResource::collection($categories),
            'branches' => Cabang::query()->orderBy('id_cabang')->get(['id_cabang', 'nama_cabang', 'kota']),
            'filters' => [
                'search' => $search,
                'kategori' => $categorySlug,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Produk $produk): Response
    {
        abort_unless($produk->is_active, 404);

        $produk->load('kategori');

        $relatedProducts = Produk::query()
            ->active()
            ->with('kategori')
            ->where('id_produk', '!=', $produk->id_produk)
            ->when($produk->id_kategori, fn ($q) => $q->where('id_kategori', $produk->id_kategori))
            ->limit(4)
            ->get();

        return Inertia::render('Store/ProductDetail', [
            'product' => ProdukResource::make($produk),
            'relatedProducts' => ProdukResource::collection($relatedProducts),
            'categories' => KategoriResource::collection(
                Kategori::query()->active()->orderBy('nama_kategori')->get(),
            ),
            'branches' => Cabang::query()->orderBy('id_cabang')->get(['id_cabang', 'nama_cabang', 'kota']),
        ]);
    }
}
