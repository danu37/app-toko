<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaksi\StoreTransaksiRequest;
use App\Http\Resources\CabangResource;
use App\Http\Resources\PelangganResource;
use App\Http\Resources\ProdukResource;
use App\Http\Resources\TransaksiResource;
use App\Http\Resources\UserResource;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransaksiController extends Controller
{
    public function __construct(
        private readonly TransaksiService $transaksiService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaksi::class);

        $search = $request->string('search')->toString();
        $tanggal = $request->string('tanggal')->toString();
        $cabangId = $request->integer('cabang') ?: null;
        $status = $request->string('status')->toString();
        $metode = $request->string('metode')->toString();

        $transactions = Transaksi::query()
            ->with(['pelanggan', 'cabang', 'user'])
            ->withCount('detail')
            ->when($search, function ($query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('kode_transaksi', 'like', $like)
                        ->orWhere('nama_kasir', 'like', $like)
                        ->orWhereHas('pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', $like));
                });
            })
            ->when($tanggal, fn ($q) => $q->where('tanggal', $tanggal))
            ->when($cabangId, fn ($q) => $q->where('id_cabang', $cabangId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($metode, fn ($q) => $q->where('metode_pembayaran', $metode))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transaksi/Index', [
            'transactions' => TransaksiResource::collection($transactions),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'filters' => [
                'search' => $search,
                'tanggal' => $tanggal,
                'cabang' => $cabangId,
                'status' => $status,
                'metode' => $metode,
            ],
        ]);
    }

    public function riwayat(Request $request): Response
    {
        $this->authorize('viewAny', Transaksi::class);
        Gate::authorize('view-transaction-history');

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $cabangId = $request->integer('cabang') ?: null;
        $status = $request->string('status')->toString();
        $kasirId = $request->user()->isAdmin() ? ($request->integer('kasir') ?: null) : null;

        $transactions = Transaksi::query()
            ->with(['pelanggan', 'cabang', 'user', 'detail'])
            ->withCount('detail')
            ->when($search, function ($query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('kode_transaksi', 'like', $like)
                        ->orWhere('nama_kasir', 'like', $like)
                        ->orWhereHas('pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', $like))
                        ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', $like))
                        ->orWhereHas('detail', fn ($sub) => $sub->where('nama_produk', 'like', $like));
                });
            })
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->when($cabangId, fn ($q) => $q->where('id_cabang', $cabangId))
            ->when($kasirId, fn ($q) => $q->where('user_id', $kasirId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transaksi/Riwayat', [
            'transactions' => TransaksiResource::collection($transactions),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'cashiers' => $request->user()->isAdmin()
                ? UserResource::collection(User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'id_cabang', 'is_active']))
                : [],
            'filters' => [
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'cabang' => $cabangId,
                'kasir' => $kasirId,
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transaksi::class);

        $products = Produk::query()
            ->active()
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

        $customers = Pelanggan::query()
            ->orderBy('nama_pelanggan')
            ->get(['id_pelanggan', 'nama_pelanggan', 'kota', 'no_hp', 'alamat']);

        $branches = Cabang::query()
            ->orderBy('nama_cabang')
            ->get(['id_cabang', 'nama_cabang', 'kota']);

        return Inertia::render('Transaksi/Create', [
            'products' => ProdukResource::collection($products),
            'customers' => PelangganResource::collection($customers),
            'branches' => CabangResource::collection($branches),
            'preselectedCustomerId' => $request->integer('customer') ?: null,
        ]);
    }

    public function store(StoreTransaksiRequest $request): RedirectResponse
    {
        $transaksi = $this->transaksiService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('transaksi.show', $transaksi)
            ->with('success', "Transaksi {$transaksi->kode_transaksi} berhasil diproses.");
    }

    public function show(Transaksi $transaksi): Response
    {
        $this->authorize('view', $transaksi);

        $transaksi->load(['pelanggan', 'cabang', 'user', 'detail.produk']);

        return Inertia::render('Transaksi/Show', [
            'transaction' => TransaksiResource::make($transaksi),
            'storeInfo' => config('store'),
        ]);
    }

    public function nota(Request $request, Transaksi $transaksi): Response
    {
        $this->authorize('view', $transaksi);

        $transaksi->load(['pelanggan', 'cabang', 'user', 'detail.produk']);

        $requestedSize = $request->string('size')->toString();
        $initialSize = in_array($requestedSize, ['40mm', '58mm', '80mm'], true)
            ? $requestedSize
            : '58mm';

        return Inertia::render('Transaksi/Receipt', [
            'transaction' => TransaksiResource::make($transaksi),
            'storeInfo' => config('store'),
            'initialSize' => $initialSize,
        ]);
    }

    public function destroy(Transaksi $transaksi): RedirectResponse
    {
        $this->authorize('delete', $transaksi);

        return redirect()
            ->route('transaksi.index')
            ->with('error', 'Penghapusan transaksi tidak diaktifkan demi integritas audit.');
    }
}
