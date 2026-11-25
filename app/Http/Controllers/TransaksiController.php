<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaksi\KasirLaporanFilterRequest;
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
use App\Services\LaporanService;
use App\Services\TransaksiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransaksiController extends Controller
{
    public function __construct(
        private readonly TransaksiService $transaksiService,
        private readonly LaporanService $laporanService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaksi::class);

        $search = $request->string('search')->toString();
        $tanggal = $request->string('tanggal')->toString();
        $cabangId = $request->integer('cabang') ?: null;
        $kasirId = $request->user()->isAdmin() ? ($request->integer('kasir') ?: null) : null;
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
                        ->orWhereHas('pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', $like))
                        ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', $like))
                        ->orWhereHas('detail', fn ($sub) => $sub->where('nama_produk', 'like', $like));
                });
            })
            ->when($tanggal, fn ($q) => $q->where('tanggal', $tanggal))
            ->when($cabangId, fn ($q) => $q->where('id_cabang', $cabangId))
            ->when($kasirId, fn ($q) => $q->where('user_id', $kasirId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($metode, fn ($q) => $q->where('metode_pembayaran', $metode))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transaksi/Index', [
            'transactions' => TransaksiResource::collection($transactions),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'cashiers' => $request->user()->isAdmin()
                ? UserResource::collection(
                    User::query()
                        ->where('role', User::ROLE_KASIR)
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'role', 'id_cabang', 'is_active'])
                )
                : [],
            'filters' => [
                'search' => $search,
                'tanggal' => $tanggal,
                'cabang' => $cabangId,
                'kasir' => $kasirId,
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
                ? UserResource::collection(
                    User::query()
                        ->where('role', User::ROLE_KASIR)
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'role', 'id_cabang', 'is_active'])
                )
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

    public function laporan(KasirLaporanFilterRequest $request): Response
    {
        $this->authorize('viewAny', Transaksi::class);
        Gate::authorize('view-transaction-history');

        $filters = $request->filters();

        // Default ke hari ini pada kunjungan awal agar kasir langsung siap mencetak laporan shift harian
        if ($request->query() === []) {
            $today = now()->toDateString();
            $filters['start_date'] = $today;
            $filters['end_date'] = $today;
        }

        $actor = $request->user();
        $report = $this->laporanService->getCashierOperationalReport(
            $actor,
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['status'],
            $filters['metode'],
            $filters['kasir'],
            $filters['search'],
        );

        return Inertia::render('Transaksi/Laporan', [
            'aggregates' => $report['aggregates'],
            'paymentMethodRecap' => $report['paymentMethodRecap'],
            'transactions' => TransaksiResource::collection($report['transactions']),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'cashiers' => $actor->isAdmin()
                ? UserResource::collection(
                    User::query()
                        ->where('role', User::ROLE_KASIR)
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'role', 'id_cabang', 'is_active'])
                )
                : [],
            'storeInfo' => config('store'),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
                'cabang' => $filters['cabang'] ?? '',
                'kasir' => $actor->isAdmin() ? ($filters['kasir'] ?? '') : '',
                'status' => $filters['status'] ?? '',
                'metode' => $filters['metode'] ?? '',
            ],
        ]);
    }

    public function laporanPdf(KasirLaporanFilterRequest $request): HttpResponse
    {
        Gate::authorize('view-transaction-history');

        $filters = $request->filters();

        if ($request->query() === []) {
            $today = now()->toDateString();
            $filters['start_date'] = $today;
            $filters['end_date'] = $today;
        }

        $actor = $request->user();
        $report = $this->laporanService->getCashierOperationalReport(
            $actor,
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['status'],
            $filters['metode'],
            $filters['kasir'],
            $filters['search'],
        );

        $filterSummary = [];
        if (! $actor->isAdmin()) {
            $filterSummary[] = "Kasir: {$actor->name}";
        } elseif ($filters['kasir']) {
            $kasirName = User::query()->whereKey($filters['kasir'])->value('name');
            if ($kasirName) {
                $filterSummary[] = "Kasir: {$kasirName}";
            }
        }
        if ($filters['cabang']) {
            $cabangName = Cabang::query()->whereKey($filters['cabang'])->value('nama_cabang');
            if ($cabangName) {
                $filterSummary[] = "Cabang: {$cabangName}";
            }
        }
        if ($filters['metode']) {
            $filterSummary[] = 'Metode: '.strtoupper($filters['metode']);
        }
        if ($filters['status']) {
            $filterSummary[] = "Status: {$filters['status']}";
        }
        if ($filters['search']) {
            $filterSummary[] = "Kata Kunci: \"{$filters['search']}\"";
        }

        $startLabel = $filters['start_date'] ?: 'Awal';
        $endLabel = $filters['end_date'] ?: 'Sekarang';
        $periodLabel = ($filters['start_date'] || $filters['end_date'])
            ? "{$startLabel} s/d {$endLabel}"
            : 'Semua Periode';

        $pdf = Pdf::loadView('pdf.kasir-laporan', [
            'title' => $actor->isAdmin() ? 'Laporan Operasional Transaksi' : 'Laporan Shift & Transaksi Kasir',
            'storeInfo' => config('store'),
            'actor' => $actor,
            'periodLabel' => $periodLabel,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'filterSummary' => $filterSummary,
            'aggregates' => $report['aggregates'],
            'paymentMethodRecap' => $report['paymentMethodRecap'],
            'transactions' => $report['transactions'],
        ])->setPaper('a4', 'portrait');

        $fileSuffix = now()->format('Ymd-His');
        $filename = "laporan-kasir-{$fileSuffix}.pdf";

        return $pdf->download($filename);
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
                'barcode',
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
            'qrisInfo' => config('store.qris'),
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

        $transaksi->load(['pelanggan', 'cabang', 'user', 'verifier', 'detail.produk']);

        return Inertia::render('Transaksi/Show', [
            'transaction' => TransaksiResource::make($transaksi),
            'storeInfo' => config('store'),
        ]);
    }

    public function verify(Request $request, Transaksi $transaksi): RedirectResponse
    {
        $this->authorize('verify', $transaksi);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'catatan_verifikasi' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $this->transaksiService->verifyPayment(
            $transaksi,
            $request->user(),
            $validated['decision'],
            $validated['catatan_verifikasi'] ?? null,
        );

        $kode = $updated->kode_transaksi ?: "#TRX-{$updated->id_transaksi}";
        $message = $validated['decision'] === 'approve'
            ? "Pembayaran transaksi {$kode} telah disetujui dan diselesaikan."
            : "Pembayaran transaksi {$kode} ditolak dan stok produk telah dikembalikan.";

        return redirect()
            ->back()
            ->with('success', $message);
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

    public function destroy(Request $request, Transaksi $transaksi): RedirectResponse
    {
        $this->authorize('delete', $transaksi);

        $transaksiId = $transaksi->id_transaksi;
        $kode = $transaksi->kode_transaksi ?: "#TRX-{$transaksiId}";
        $this->transaksiService->delete($transaksi);

        $previousUrl = (string) url()->previous();
        $isFromDetail = str_contains($previousUrl, "/transaksi/{$transaksiId}");

        if ($isFromDetail || $previousUrl === '' || $previousUrl === $request->fullUrl()) {
            return redirect()
                ->route('transaksi.index')
                ->with('success', "Transaksi {$kode} berhasil dihapus.");
        }

        return redirect()
            ->back()
            ->with('success', "Transaksi {$kode} berhasil dihapus.");
    }
}
