<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kategori\StoreKategoriRequest;
use App\Http\Requests\Kategori\UpdateKategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use App\Services\ProdukService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KategoriController extends Controller
{
    public function __construct(
        private readonly ProdukService $produkService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Kategori::class);

        $search = $request->string('search')->toString();

        $categories = Kategori::query()
            ->withCount('produk')
            ->when($search, fn ($q) => $q->where('nama_kategori', 'like', '%'.trim($search).'%'))
            ->orderBy('nama_kategori')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Kategori/Index', [
            'categories' => KategoriResource::collection($categories),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreKategoriRequest $request): RedirectResponse
    {
        $this->produkService->createCategory(
            $request->validated(),
            $request->file('gambar'),
        );

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(UpdateKategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $this->produkService->updateCategory(
            $kategori,
            $request->validated(),
            $request->file('gambar'),
        );

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        $this->authorize('delete', $kategori);

        try {
            $kategori->delete();
        } catch (QueryException) {
            return redirect()
                ->route('kategori.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.');
        }

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
