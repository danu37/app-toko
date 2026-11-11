<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pelanggan\StorePelangganRequest;
use App\Http\Requests\Pelanggan\UpdatePelangganRequest;
use App\Http\Resources\PelangganResource;
use App\Http\Resources\TransaksiResource;
use App\Models\Pelanggan;
use App\Models\Transaksi;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PelangganController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Pelanggan::class);

        $search = $request->string('search')->toString();
        $kota = $request->string('kota')->toString();

        $customers = Pelanggan::query()
            ->withCount('transaksi')
            ->withSum('transaksi', 'total_belanja')
            ->search($search)
            ->when($kota, fn ($q) => $q->where('kota', $kota))
            ->orderByDesc('id_pelanggan')
            ->paginate(15)
            ->withQueryString();

        $cities = Pelanggan::query()
            ->whereNotNull('kota')
            ->where('kota', '!=', '')
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota')
            ->values();

        return Inertia::render('Pelanggan/Index', [
            'customers' => PelangganResource::collection($customers),
            'cities' => $cities,
            'filters' => [
                'search' => $search,
                'kota' => $kota,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Pelanggan::class);

        return Inertia::render('Pelanggan/Create', [
            'from' => $request->string('from')->toString() === 'pos' ? 'pos' : null,
        ]);
    }

    public function store(StorePelangganRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $from = $validated['from'] ?? null;
        unset($validated['from']);

        $pelanggan = Pelanggan::create($validated);

        if ($from === 'pos') {
            return redirect()
                ->route('transaksi.create', ['customer' => $pelanggan->id_pelanggan])
                ->with('success', "Pelanggan {$pelanggan->nama_pelanggan} berhasil ditambahkan dan siap dipilih di POS.");
        }

        return redirect()
            ->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Pelanggan $pelanggan): Response
    {
        $this->authorize('view', $pelanggan);

        $pelanggan->loadCount('transaksi')
            ->loadSum('transaksi', 'total_belanja');

        $aggregate = $pelanggan->transaksi()
            ->where(function ($q) {
                $q->where('status', '!=', Transaksi::STATUS_DIBATALKAN)
                    ->orWhereNull('status');
            })
            ->selectRaw('COUNT(*) as total_selesai, COALESCE(SUM(total_belanja), 0) as total_belanja, COALESCE(AVG(total_belanja), 0) as rata_rata, MAX(tanggal) as transaksi_terakhir')
            ->first();

        $transactions = $pelanggan->transaksi()
            ->with(['cabang:id_cabang,nama_cabang', 'user:id,name'])
            ->withCount('detail')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Pelanggan/Show', [
            'customer' => PelangganResource::make($pelanggan),
            'customerSummary' => [
                'total_transaksi' => (int) ($pelanggan->transaksi_count ?? 0),
                'total_transaksi_selesai' => (int) ($aggregate->total_selesai ?? 0),
                'total_belanja' => (float) ($aggregate->total_belanja ?? 0),
                'rata_rata_belanja' => round((float) ($aggregate->rata_rata ?? 0), 2),
                'transaksi_terakhir' => $aggregate->transaksi_terakhir,
            ],
            'transactions' => TransaksiResource::collection($transactions),
        ]);
    }

    public function edit(Pelanggan $pelanggan): Response
    {
        $this->authorize('update', $pelanggan);

        return Inertia::render('Pelanggan/Edit', [
            'customer' => PelangganResource::make($pelanggan),
        ]);
    }

    public function update(UpdatePelangganRequest $request, Pelanggan $pelanggan): RedirectResponse
    {
        $pelanggan->update($request->validated());

        return redirect()
            ->route('pelanggan.index')
            ->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Pelanggan $pelanggan): RedirectResponse
    {
        $this->authorize('delete', $pelanggan);

        try {
            $pelanggan->delete();
        } catch (QueryException) {
            return redirect()
                ->route('pelanggan.index')
                ->with('error', 'Pelanggan tidak dapat dihapus karena masih memiliki riwayat transaksi.');
        }

        return redirect()
            ->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
