<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cabang\StoreCabangRequest;
use App\Http\Requests\Cabang\UpdateCabangRequest;
use App\Http\Resources\CabangResource;
use App\Models\Cabang;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CabangController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Cabang::class);

        $search = $request->string('search')->toString();

        $branches = Cabang::query()
            ->withCount(['users', 'transaksi'])
            ->when($search, function ($query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('nama_cabang', 'like', $like)
                        ->orWhere('kota', 'like', $like);
                });
            })
            ->orderBy('id_cabang')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Cabang/Index', [
            'branches' => CabangResource::collection($branches),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Cabang::class);

        return Inertia::render('Cabang/Create');
    }

    public function store(StoreCabangRequest $request): RedirectResponse
    {
        Cabang::create($request->validated());

        return redirect()
            ->route('cabang.index')
            ->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Cabang $cabang): Response
    {
        $this->authorize('update', $cabang);

        return Inertia::render('Cabang/Edit', [
            'branch' => CabangResource::make($cabang),
        ]);
    }

    public function update(UpdateCabangRequest $request, Cabang $cabang): RedirectResponse
    {
        $cabang->update($request->validated());

        return redirect()
            ->route('cabang.index')
            ->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Cabang $cabang): RedirectResponse
    {
        $this->authorize('delete', $cabang);

        try {
            $cabang->delete();
        } catch (QueryException) {
            return redirect()
                ->route('cabang.index')
                ->with('error', 'Cabang tidak dapat dihapus karena masih terhubung dengan data lain.');
        }

        return redirect()
            ->route('cabang.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
